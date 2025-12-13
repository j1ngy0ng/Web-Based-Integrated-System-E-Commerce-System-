<?php
function buildDashboardPrompt(array $data): string {
    return "
You are an e-commerce business analyst.

Analyze the dashboard metrics and return insights in JSON:

{
  \"salesInsight\": \"\",
  \"inventoryRecommendation\": \"\",
  \"customerServiceInsight\": \"\",
  \"marketingSuggestion\": \"\"
}

Dashboard Data:
" . json_encode($data, JSON_PRETTY_PRINT);
}
