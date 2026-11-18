$token = "EAAThE9Sfl9YBSCmgHyGRYYinU0OXEjtfseEyd2ZAu42dnOaYvZATMsbZBggti7prnQ50mcuGoynLAzaLPHLgXeJoapk5ToauFDUcotyO1QimmgtWZBrbRxmTrrHEAZBYzV1lz2fZBfO5Hb3lrk0WV7OoSasoq6GDqUZBbTt6P9FRjpTZAif2fVBpKFyjKGORze9AZBRBn8hWu973RlSwBfyEDBE9JS9UgUf6GZCQ85HtZA1IftFvpJDeDFp8BJ9tpmbYuuCZAcNWLmksIaHstJTf9Ssi"
$phoneId = "1194759783721722"

$headers = @{
    "Authorization" = "Bearer $token"
    "Content-Type" = "application/json"
}

$body = @{
    "messaging_product" = "whatsapp"
    "to" = "918509335091"
    "type" = "text"
    "text" = @{
        "body" = "test"
    }
} | ConvertTo-Json

$response = Invoke-RestMethod -Uri "https://graph.facebook.com/debug_token?input_token=$token&access_token=$token" -Method Get
$response | ConvertTo-Json -Depth 5
