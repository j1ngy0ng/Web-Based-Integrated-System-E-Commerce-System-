<?php
require_once '../_base.php';
auth('Admin');

header('Content-Type: application/json');

// ---------------------
// 1. Collect dashboard stats
// ---------------------
$totalRevenue = $_db->query("SELECT SUM(TotalAmount) FROM `order` WHERE status IN ('Paid','Shipped','Completed')")->fetchColumn() ?: 0;
$totalOrders = $_db->query("SELECT COUNT(*) FROM `order`")->fetchColumn() ?: 0;
$pendingOrders = $_db->query("SELECT COUNT(*) FROM `order` WHERE status = 'Pending'")->fetchColumn() ?: 0;
$lowStockThreshold = 10;
$lowStockCount = $_db->query("SELECT COUNT(*) FROM product WHERE stock <= $lowStockThreshold")->fetchColumn() ?: 0;
$totalUnreadChats = $_db->query("
    SELECT COUNT(*) as unread_count
    FROM chat_room cr
    JOIN chat_message cm ON cr.roomID = cm.roomID
    WHERE cm.created_at > IFNULL(cr.last_read_at,'1970-01-01')
")->fetchColumn() ?: 0;

// ---------------------
// 2. OpenAI API call
// ---------------------
$openai_api_key = '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'; // <-- replace

$prompt = <<<PROMPT
You are a smart e-commerce assistant. 
Analyze the following stats and provide detailed recommendations in strict JSON format with keys:
salesInsight, inventoryRecommendation, customerServiceInsight, marketingSuggestion.

Each key must contain a single string field called "recommendation". 
This string should be multi-sentence and include:
- current status,
- potential consequences if not addressed,
- step-by-step actionable recommendations,
- reasoning behind each action.

Stats:
- Total Revenue: $totalRevenue
- Total Orders: $totalOrders
- Pending Orders: $pendingOrders
- Low Stock Items: $lowStockCount
- Unread Messages: $totalUnreadChats

Return strictly valid JSON only. Example format:

{
  "salesInsight": { "recommendation": "..." },
  "inventoryRecommendation": { "recommendation": "..." },
  "customerServiceInsight": { "recommendation": "..." },
  "marketingSuggestion": { "recommendation": "..." }
}
PROMPT;

// Prepare request
$data = [
    'model' => 'gpt-4-0613',
    'messages' => [
        ['role'=>'system', 'content'=>'You are an e-commerce dashboard assistant.'],
        ['role'=>'user', 'content'=>$prompt]
    ],
    'temperature' => 0.7
];

$ch = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer $openai_api_key"
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

// ---------------------
// 3. Handle errors
// ---------------------
if($err){
    echo json_encode([
        'salesInsight' => ['recommendation'=>'Sales: Unable to fetch AI insights.'],
        'inventoryRecommendation' => ['recommendation'=>'Inventory: Check stock levels manually.'],
        'customerServiceInsight' => ['recommendation'=>'Customer Service: Review unread messages.'],
        'marketingSuggestion' => ['recommendation'=>'Marketing: Consider promotions.']
    ]);
    exit;
}

// ---------------------
// 4. Parse AI response
// ---------------------
$ai_response = json_decode($response, true);
$ai_text = $ai_response['choices'][0]['message']['content'] ?? '';
$insights = json_decode($ai_text, true);

// Fallback if parsing fails
if(!$insights || !isset($insights['salesInsight'])){
    $insights = [
        'salesInsight' => ['recommendation'=>'Sales: Unable to parse AI response.'],
        'inventoryRecommendation' => ['recommendation'=>'Inventory: Check stock levels manually.'],
        'customerServiceInsight' => ['recommendation'=>'Customer Service: Review unread messages.'],
        'marketingSuggestion' => ['recommendation'=>'Marketing: Consider promotions.']
    ];
}

echo json_encode($insights);
