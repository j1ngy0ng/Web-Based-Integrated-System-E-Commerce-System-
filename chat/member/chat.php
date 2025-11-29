<?php
include '../../_base.php';
auth();

// Get or create chat room for the member
$userID = $_user->id;

$stm = $_db->prepare("SELECT * FROM chat_room WHERE userID = ?");
$stm->execute([$userID]);
$room = $stm->fetch();

if (!$room) {
    $stm = $_db->prepare("INSERT INTO chat_room (userID) VALUES (?)");
    $stm->execute([$userID]);
    $roomID = $_db->lastInsertId();
} else {
    $roomID = $room->roomID;
}

$_title = "Support Chat";
include '../../navbar.php';
?>

<style>
/* Container */
.chat-wrapper {
    width: 90%;
    max-width: 900px;
    margin: 30px auto;
}

/* Chat Box */
.chat-container {
    background: #fff;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
}

/* Header */
.chat-header {
    padding: 15px;
    background: #007bff;
    color: white;
    font-size: 18px;
    font-weight: bold;
}

/* Message window */
.chat-window {
    height: 450px;
    padding: 20px;
    overflow-y: auto;
    background: #f4f6f9;
}

/* Message bubbles */
.msg-me, .msg-admin {
    max-width: 70%;
    padding: 10px 15px;
    margin: 10px 0;
    border-radius: 12px;
    clear: both;
    font-size: 15px;
    line-height: 1.4;
}

.msg-me {
    background: #007bff;
    float: right;
    border-bottom-right-radius: 0;
}

.msg-admin {
    background: #E2E2E2;
    float: left;
    border-bottom-left-radius: 0;
}

/* Input area */
.chat-input-area {
    padding: 15px;
    display: flex;
    gap: 10px;
    background: white;
    border-top: 1px solid #ddd;
}

.chat-input-area input {
    flex: 1;
    padding: 12px;
    border-radius: 8px;
    border: 1px solid #ccc;
}

.chat-input-area button {
    padding: 12px 20px;
    background: #28a745;
    border: none;
    color: white;
    border-radius: 8px;
    font-weight: bold;
    cursor: pointer;
}

.chat-input-area button:hover {
    background: #1f7a33;
}

/* Fade-in animation to reduce blink */
.msg-me, .msg-admin {
    animation: fadeIn 0.15s ease-in-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<div class="chat-wrapper">
    <div class="chat-container">

        <div class="chat-header">
            Support Chat
        </div>

        <div class="chat-window" id="chat-box"></div>

        <div class="chat-input-area">
            <input id="msg" type="text" placeholder="Type your message...">
            <button onclick="sendMsg()">Send</button>
        </div>

    </div>
</div>

<script>
let roomID = <?= $roomID ?>;
let userID = <?= $userID ?>;
let lastCount = 0;

function escapeHTML(text) {
    let div = document.createElement("div");
    div.innerText = text;
    return div.innerHTML;
}

function loadMessages() {
    fetch("../fetch_messages.php?roomID=" + roomID)
        .then(r => r.json())
        .then(messages => {

            // Skip refresh if no new messages (prevents blinking)
            if (messages.length === lastCount) return;
            lastCount = messages.length;

            let box = document.getElementById("chat-box");
            box.innerHTML = "";

            messages.forEach(m => {
                let bubble = document.createElement("div");
                if (m.senderID == userID) {
                    bubble.className = "msg-me";
                } else {
                    bubble.className = "msg-admin";
                }
                bubble.innerHTML = escapeHTML(m.message);
                box.appendChild(bubble);
            });

            // Auto scroll to bottom
            box.scrollTop = box.scrollHeight;
        });
}

function sendMsg() {
    let text = document.getElementById("msg").value;
    if (!text.trim()) return;

    fetch("../send_message.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "roomID=" + roomID + "&message=" + encodeURIComponent(text)
    });

    document.getElementById("msg").value = "";
}

// Refresh messages every 1 second
setInterval(loadMessages, 1000);
loadMessages();
</script>

<?php include '../../footer.php'; ?>
