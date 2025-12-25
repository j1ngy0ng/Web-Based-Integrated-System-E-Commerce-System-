<?php
include_once __DIR__ . '/../_base.php';

/* ================= Calendar Setup ================= */
$year  = req('year') ?: date('Y');
$month = req('month') ?: date('n');

$a = new DateTime("$year-$month");
$b = new DateTime("last day of $year-$month");

if ($a->format('N') != 1) $a->modify('previous monday');
if ($b->format('N') != 7) $b->modify('next sunday');

/* ================= Fetch Events ================= */
// Fetch events
$stm = $_db->prepare("
    SELECT e.*, GROUP_CONCAT(pe.product_id) AS product_ids
    FROM product_event e
    LEFT JOIN product_event_item pe ON e.id = pe.event_id
    WHERE e.event_date BETWEEN ? AND ?
    GROUP BY e.id
");
$stm->execute([$a->format('Y-m-d'), $b->format('Y-m-d')]);
$events = $stm->fetchAll(PDO::FETCH_OBJ);

// Attach products details
foreach ($events as $e) {
    $e->products = [];
    if ($e->product_ids) {
        $ids = explode(',', $e->product_ids);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stm2 = $_db->prepare("SELECT productID, name FROM product WHERE productID IN ($placeholders)");
        $stm2->execute($ids);
        $e->products = $stm2->fetchAll(PDO::FETCH_OBJ);
    }
    $data[$e->event_date][] = $e;
}


/* ================= Fetch Products ================= */
$products = $_db
    ->query("SELECT productID, name FROM product")
    ->fetchAll(PDO::FETCH_OBJ);
?>

<!-- ================= Controls ================= -->
<form method="get">
    <select name="year" onchange="this.form.submit()">
        <?php for ($y = date('Y') - 4; $y <= date('Y'); $y++): ?>
            <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>>
                <?= $y ?>
            </option>
        <?php endfor; ?>
    </select>

    <select name="month" onchange="this.form.submit()">
        <?php foreach (get_months() as $m => $name): ?>
            <option value="<?= $m ?>" <?= $m == $month ? 'selected' : '' ?>>
                <?= $name ?>
            </option>
        <?php endforeach; ?>
    </select>
</form>


<!-- ================= Calendar ================= -->
<div class="cal">
    <h3>Mon</h3>
    <h3>Tue</h3>
    <h3>Wed</h3>
    <h3>Thu</h3>
    <h3>Fri</h3>
    <h3>Sat</h3>
    <h3>Sun</h3>

    <?php
    for ($d = clone $a; $d <= $b; $d->modify('+1 day')) {
        $date = $d->format('Y-m-d');
        $x = $d->format('n') != $month ? 'x' : '';
        echo "<div class='day $x' data-date='$date'>";
        echo "<b>{$d->format('d')}</b>";

        foreach ($data[$date] ?? [] as $e) {
            $eventData = htmlspecialchars(json_encode([
                'id' => $e->id,
                'name' => $e->name,
                'description' => $e->description,
                'start_time' => $e->start_time,
                'end_time' => $e->end_time,
                'is_promo' => $e->is_promo,
                'products' => $e->products ?? [],
            ]), ENT_QUOTES);

            echo "<div class='event' data-event='$eventData'>"
                . htmlspecialchars($e->name)
                . "</div>";
        }



        echo "</div>";
    }
    ?>
</div>

<!-- ================= Modal ================= -->
<div id="eventModal" class="modal">


    <div class="box">

        <h4 id="modalDate"></h4>

        <form method="post" action="Event/saveEvent.php" class="event-form">
            <input type="hidden" name="year" value="<?= $year ?>">
            <input type="hidden" name="month" value="<?= $month ?>">


            <input type="hidden" name="event_date" id="event_date">

            <div class="form-row">
                <label>Event Name</label>
                <input type="text" name="name" required>
            </div>

            <div class="form-row">
                <label>Event Description</label>
                <textarea name="description" rows="3"></textarea>
            </div>

            <div class="form-row">
                <label>Start Time</label>
                <input type="time" name="start_time">
            </div>

            <div class="form-row">
                <label>End Time</label>
                <input type="time" name="end_time">
            </div>


            <div class="form-row inline">
                <label>Promotion Event?</label>
                <select name="is_promo" id="is_promo">
                    <option value="0">No</option>
                    <option value="1">Yes</option>
                </select>
            </div>


            <!-- ===== Promo Section ===== -->
            <div id="promo-section" style="display:none">
                <h4>Promotion Products</h4>

                <div id="products"></div>

                <div class="product-select-row">
                    <select id="promo-product-select">
                        <option value="">-- Select Product --</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?= $p->productID ?>"><?= htmlspecialchars($p->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" id="promo-add-btn" class="btn-add">+</button>
                </div>
            </div>



            <button class="btn-save">Save Event</button>
            <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
            <button type="button" class="btn-delete" onclick="deleteEvent()">Delete Event</button>



        </form>
    </div>
</div>

<!-- ================= Scripts ================= -->
<script>
    /* Open modal on day click */
    document.querySelectorAll('.event').forEach(ev => {
        ev.onclick = e => {
            const data = JSON.parse(ev.dataset.event);

            eventModal.style.display = 'flex';
            modalDate.innerText = ev.closest('.day').dataset.date;
            event_date.value = ev.closest('.day').dataset.date;

            const form = document.querySelector('.event-form');
            form.name.value = data.name;
            form.description.value = data.description;
            form.start_time.value = data.start_time || '';
            form.end_time.value = data.end_time || '';
            form.is_promo.value = data.is_promo;

            document.getElementById('promo-section').style.display =
                data.is_promo == 1 ? 'block' : 'none';

            // Load products if promo
            if (data.is_promo == 1 && data.products) {
                const productsContainer = document.getElementById('products');
                productsContainer.innerHTML = '';
                data.products.forEach(p => {
                    productsContainer.insertAdjacentHTML('beforeend', `
                    <div class="product-row">
                        <input type="hidden" name="product_id[]" value="${p.productID}">
                        <span>${p.name}</span>
                        <button type="button" class="btn-add" onclick="this.parentElement.remove()">−</button>
                    </div>
                `);
                });
            }

            // Save event ID
            if (!form.querySelector('input[name="id"]')) {
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                form.appendChild(idInput);
            }
            form.querySelector('input[name="id"]').value = data.id;
        };
    });


    // Open modal on day click for creating new event
    document.querySelectorAll('.day').forEach(day => {
        day.addEventListener('click', e => {
            if (e.target.classList.contains('event')) return;

            const modal = document.getElementById('eventModal');
            modal.style.display = 'flex';

            const date = day.dataset.date;
            document.getElementById('modalDate').innerText = date;
            document.getElementById('event_date').value = date;

            const form = modal.querySelector('form');
            form.reset();
            document.getElementById('promo-section').style.display = 'none';
            document.getElementById('products').innerHTML = '';

            const idInput = form.querySelector('input[name="id"]');
            if (idInput) idInput.remove();

            form.start_time.value = '';
            form.end_time.value = '';
        });
    });


    document.querySelector('.event-form').addEventListener('submit', function(e) {
        const promoSelect = document.getElementById('promo-product-select');
        const productsContainer = document.getElementById('products');

        if (document.getElementById('is_promo').value == 1 && promoSelect.value) {
            // Only add if not already added
            if (!productsContainer.querySelector(`input[value="${promoSelect.value}"]`)) {
                const productName = promoSelect.options[promoSelect.selectedIndex].text;
                productsContainer.insertAdjacentHTML('beforeend', `
                <div class="product-row">
                    <input type="hidden" name="product_id[]" value="${promoSelect.value}">
                    <span>${productName}</span>
                    <button type="button" class="btn-add" onclick="this.parentElement.remove()">−</button>
                </div>
            `);
            }
        }
    });


    /* Promo toggle */
    document.getElementById('is_promo').addEventListener('change', function() {
        document.getElementById('promo-section').style.display =
            this.value == 1 ? 'block' : 'none';
    });

    /* Add selected product to promo list */
    const productsContainer = document.getElementById('products');
    const promoSelect = document.getElementById('promo-product-select');
    const addBtn = document.getElementById('promo-add-btn');

    addBtn.addEventListener('click', function() {
        const productId = promoSelect.value;

        if (!productId) {
            alert('Please select a product first!');
            return;
        }

        const productName = promoSelect.options[promoSelect.selectedIndex].text;

        productsContainer.insertAdjacentHTML('beforeend', `
        <div class="product-row">
            <input type="hidden" name="product_id[]" value="${productId}">
            <span>${productName}</span>
            <button type="button" class="btn-add" onclick="this.parentElement.remove()">−</button>
        </div>
    `);

        // Reset select
        promoSelect.value = "";
    });

    /* Auto-submit calendar year/month changes */
    $('.calendar-nav').on('change', function() {
        this.form.submit();
    });

    function closeModal() {
        const modal = document.getElementById('eventModal');
        modal.style.display = 'none';
        modal.querySelector('form').reset();
        document.getElementById('promo-section').style.display = 'none';
        document.getElementById('products').innerHTML = '';
    }

    document.querySelector('.btn-delete').style.display = eventId ? 'inline-block' : 'none';

    function deleteEvent() {
        if (!confirm("Are you sure you want to delete this event?")) return;

        const form = document.querySelector('.event-form');
        const eventId = form.querySelector('input[name="id"]').value;

        if (!eventId) return; // no event selected

        // Send request to delete
        fetch('Event/deleteEvent.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'id=' + encodeURIComponent(eventId)
            })
            .then(res => res.text())
            .then(res => {
                // Optionally show success message
                // Close modal and reload calendar
                closeModal();
                location.href = `landing_admin.php?year=<?= $year ?>&month=<?= $month ?>`;

            })
            .catch(err => alert('Error deleting event: ' + err));
    }
</script>

<!-- ================= Styles ================= -->
<style>
    .cal {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 6px
    }

    .cal h3 {
        background: #4e73df;
        color: #fff;
        text-align: center;
        padding: 6px
    }

    .day {
        background: #fff;
        padding: 6px;
        min-height: 90px;
        cursor: pointer;
        border-radius: 6px;
        display: flex;
        flex-direction: column;
        gap: 3px;
        max-height: 200px;
        overflow-y: auto;
    }


    .day.x {
        background: #f0f0f0;
        color: #999
    }

    .event {
        background: #36b9cc;
        color: #fff;
        font-size: 12px;
        padding: 2px 5px;
        margin-top: 3px;
        border-radius: 4px
    }

    .modal {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, .4);
        justify-content: center;
        align-items: center
    }

    .box {
        background: #fff;
        padding: 20px;
        border-radius: 10px;
        width: 480px;
        max-width: 90%;
    }


    .event-form {
        display: flex;
        flex-direction: column;
        gap: 14px;
        font-size: 14px
    }

    .form-row {
        display: flex;
        flex-direction: column;
        gap: 6px
    }

    .form-row.inline {
        flex-direction: row;
        align-items: center;
        gap: 10px
    }

    .product-row {
        display: grid;
        grid-template-columns: 1fr 70px 36px;
        gap: 8px;
        align-items: center
    }

    input,
    select,
    textarea {
        padding: 8px;
        border-radius: 6px;
        border: 1px solid #ccc
    }

    .btn-add {
        background: #4e73df;
        color: #fff;
        border: none;
        border-radius: 6px;
        height: 36px;
        cursor: pointer
    }

    .btn-save {
        background: #1cc88a;
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 10px;
        font-weight: 600;
        cursor: pointer
    }

    .btn-delete {
        background: #e74a3b;
        /* red color */
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 10px;
        font-weight: 600;
        cursor: pointer;
    }

    .btn-delete:hover {
        background: #c0392b;
        /* darker red on hover */
    }
</style>