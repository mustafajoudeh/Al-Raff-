
# ============================================================
# Al-Raff API - Integration Test Suite
# Environment: Windows / XAMPP / PowerShell
# ============================================================

$ErrorActionPreference = "Stop"

# ---------------- CONFIGURATION ----------------

$BaseUrl = "http://localhost/Al-Raff/API/index.php"

$AdminEmail = "admin@test.com"
$AdminPassword = "Admin@12345"

$CustomerEmail = "ahmad@test.com"
$CustomerPassword = "Ahmad@12345"

$NotFoundID = "999999999"

$ReportFolder = Join-Path $PSScriptRoot "ApiTestReports"

New-Item -ItemType Directory -Force -Path $ReportFolder | Out-Null

$TimeStamp = Get-Date -Format "yyyyMMdd_HHmmss"

$CsvReport = Join-Path $ReportFolder "API_Report_$TimeStamp.csv"
$TxtReport = Join-Path $ReportFolder "API_Report_$TimeStamp.txt"

$TestPrefix = "APITest_$TimeStamp"

# Separate sessions: cookies must not be shared.
$AdminSession = New-Object Microsoft.PowerShell.Commands.WebRequestSession
$CustomerSession = New-Object Microsoft.PowerShell.Commands.WebRequestSession
$GuestSession = New-Object Microsoft.PowerShell.Commands.WebRequestSession

$Results = [System.Collections.Generic.List[object]]::new()

# Temporary record IDs
$TestUserID = $null
$TestCategoryID = $null
$TestProductID = $null

# ---------------- HTTP CLIENT ----------------

function Invoke-Api {
    param(
        [Parameter(Mandatory = $true)]
        [string]$Method,

        [Parameter(Mandatory = $true)]
        [string]$Route,

        [hashtable]$Body = $null,

        [Microsoft.PowerShell.Commands.WebRequestSession]$Session = $null
    )

    # Normalize route
    $CleanRoute = $Route.Trim('/')

    # Encode each route segment separately.
    # This preserves / between route segments.
    $EncodedRoute = (
        $CleanRoute -split '/' |
        ForEach-Object {
            [System.Uri]::EscapeDataString($_)
        }
    ) -join '/'

    # Build URL
    $UriString = "$($BaseUrl.TrimEnd('?'))?route=$EncodedRoute"

    $StatusCode = 0
    $ResponseContent = $null
    $ResponseJson = $null
    $ErrorMessage = $null

    try {

        # Validate URL
        $Uri = [System.Uri]::new($UriString)

        if (-not $Uri.IsAbsoluteUri -or
            [string]::IsNullOrWhiteSpace($Uri.Host)) {
            throw "Invalid API URI: $UriString"
        }

        # Request parameters
        $RequestParams = @{
            Uri             = $Uri
            Method          = $Method.ToUpper()
            UseBasicParsing = $true
            ErrorAction     = "Stop"
        }

        # Attach session cookies
        if ($null -ne $Session) {
            $RequestParams.WebSession = $Session
        }

        # JSON request body
        if ($null -ne $Body) {

            $RequestParams.ContentType = "application/json; charset=utf-8"

            $RequestParams.Body = ConvertTo-Json `
                -InputObject $Body `
                -Depth 20 `
                -Compress
        }

        # Send request
        $Response = Invoke-WebRequest @RequestParams

        $StatusCode = [int]$Response.StatusCode
        $ResponseContent = $Response.Content

        # Parse JSON response
        if (-not [string]::IsNullOrWhiteSpace($ResponseContent)) {
            try {
                $ResponseJson = $ResponseContent | ConvertFrom-Json
            }
            catch {
                $ResponseJson = $null
            }
        }

    }
    catch {

        $ErrorMessage = $_.Exception.Message

        $ExceptionResponse = $_.Exception.Response

        # Extract HTTP status code and response body
        if ($null -ne $ExceptionResponse) {

            try {

                # Windows PowerShell / HttpWebResponse
                if ($ExceptionResponse -is [System.Net.HttpWebResponse]) {

                    $StatusCode = [int]$ExceptionResponse.StatusCode

                    $Stream = $ExceptionResponse.GetResponseStream()

                    if ($null -ne $Stream) {

                        $Reader = New-Object System.IO.StreamReader($Stream)

                        $ResponseContent = $Reader.ReadToEnd()

                        $Reader.Dispose()
                    }
                }
                # PowerShell Core / HttpResponseMessage
                elseif ($null -ne $ExceptionResponse.StatusCode) {

                    $StatusCode = [int]$ExceptionResponse.StatusCode

                    if ($null -ne $ExceptionResponse.Content) {
                        $ResponseContent = $ExceptionResponse.Content.ReadAsStringAsync().GetAwaiter().GetResult()
                    }
                }

                # Parse error response JSON
                if (-not [string]::IsNullOrWhiteSpace($ResponseContent)) {

                    try {
                        $ResponseJson = $ResponseContent | ConvertFrom-Json
                    }
                    catch {
                        $ResponseJson = $null
                    }
                }

            }
            catch {
                # Preserve original error if response parsing fails.
            }
        }
    }

    return [PSCustomObject]@{
        StatusCode = $StatusCode
        Json       = $ResponseJson
        Raw        = $ResponseContent
        Exception  = $ErrorMessage
        Uri        = $UriString
    }
}

# ---------------- TEST RUNNER ----------------

function Test-Api {
    param(
        [string]$Name,

        [string]$Method,

        [string]$Route,

        [object]$Body = $null,

        [object]$Session = $null,

        [int[]]$ExpectedStatus = @(200),

        [Nullable[bool]]$ExpectedSuccess = $null
    )

    $Response = Invoke-Api `
        -Method $Method `
        -Route $Route `
        -Body $Body `
        -Session $Session

    $Passed = $Response.StatusCode -in $ExpectedStatus

    if ($null -ne $ExpectedSuccess) {

        if ($null -eq $Response.Json -or
            $Response.Json.success -ne $ExpectedSuccess.Value) {

            $Passed = $false
        }
    }

    $Message = ""

    if ($null -ne $Response.Json) {
        $Message = [string]$Response.Json.message
    }

    $Errors = ""

    if ($null -ne $Response.Json -and
        $null -ne $Response.Json.errors) {

        $Errors = $Response.Json.errors |
            ConvertTo-Json -Compress -Depth 5
    }

    $Result = [PSCustomObject]@{
        Test       = $Name
        Method     = $Method
        Route      = $Route
        Expected   = ($ExpectedStatus -join ", ")
        Actual     = $Response.StatusCode
        Success    = $Passed
        APIMessage = $Message
        Errors     = $Errors
    }

    $Results.Add($Result)

    if ($Passed) {

        Write-Host "[PASS] $Name" -ForegroundColor Green

    }
    else {

        Write-Host "[FAIL] $Name | Expected: $($ExpectedStatus -join ', ') | Actual: $($Response.StatusCode)" -ForegroundColor Red

        if ($Response.Raw) {
            Write-Host "       $($Response.Raw)" -ForegroundColor DarkGray
        }

        if ($Response.Exception) {
            Write-Host "       $($Response.Exception)" -ForegroundColor DarkGray
        }

        if ($Response.StatusCode -eq 0) {
            Write-Host "       Request URI: $($Response.Uri)" -ForegroundColor Yellow
        }
    }

    return $Response
}

# ---------------- RESPONSE ID HELPER ----------------

function Get-ResponseID {
    param(
        [object]$Response,

        [string[]]$PossibleNames
    )

    if ($null -eq $Response.Json -or
        $null -eq $Response.Json.data) {

        return $null
    }

    foreach ($Name in $PossibleNames) {

        $Property = $Response.Json.data.PSObject.Properties[$Name]

        if ($null -ne $Property -and
            $null -ne $Property.Value) {

            try {
                return [int]$Property.Value
            }
            catch {
                return $null
            }
        }
    }

    return $null
}

# ============================================================
# 0. CONNECTION CHECK
# ============================================================

Write-Host "`n========== API CONNECTION CHECK ==========" -ForegroundColor Cyan

$HealthCheck = Invoke-Api -Method "GET" -Route "/health"

if ($HealthCheck.StatusCode -ne 200) {

    Write-Host "[FATAL] Cannot connect to API." -ForegroundColor Red

    Write-Host "URL: $($HealthCheck.Uri)" -ForegroundColor Yellow

    Write-Host "Status: $($HealthCheck.StatusCode)" -ForegroundColor Yellow

    if ($HealthCheck.Exception) {
        Write-Host "Error: $($HealthCheck.Exception)" -ForegroundColor Red
    }

    if ($HealthCheck.Raw) {
        Write-Host $HealthCheck.Raw
    }

    throw "API connection check failed. Test suite stopped."
}

Write-Host "[OK] API connection established." -ForegroundColor Green

# ============================================================
# 1. HEALTH
# ============================================================

Write-Host "`n========== HEALTH ==========" -ForegroundColor Cyan

Test-Api "Health endpoint" GET "/health" `
    -ExpectedStatus @(200) -ExpectedSuccess $true | Out-Null

Test-Api "Health rejects POST" POST "/health" `
    -ExpectedStatus @(405) | Out-Null

Test-Api "Unknown route returns 404" GET "/this-route-does-not-exist" `
    -ExpectedStatus @(404) | Out-Null

# ============================================================
# 2. AUTHENTICATION - GUEST
# ============================================================

Write-Host "`n========== AUTHENTICATION / GUEST ==========" -ForegroundColor Cyan

Test-Api "Guest cannot access /auth/me" GET "/auth/me" `
    -Session $GuestSession -ExpectedStatus @(401) | Out-Null

Test-Api "Guest cannot logout" POST "/auth/logout" `
    -Session $GuestSession -ExpectedStatus @(401) | Out-Null

Test-Api "Guest cannot access admin customers" GET "/admin/customers" `
    -Session $GuestSession -ExpectedStatus @(401) | Out-Null

Test-Api "Guest cannot access admin users" GET "/admin/users/1" `
    -Session $GuestSession -ExpectedStatus @(401) | Out-Null

# ============================================================
# 3. LOGIN VALIDATION
# ============================================================

Write-Host "`n========== LOGIN VALIDATION ==========" -ForegroundColor Cyan

Test-Api "Login missing body" POST "/auth/login" `
    -Session $GuestSession -ExpectedStatus @(400, 422) | Out-Null

Test-Api "Login invalid email format" POST "/auth/login" `
    @{ email = "invalid-email"; password = "SomePassword123" } `
    -Session $GuestSession -ExpectedStatus @(422) | Out-Null

Test-Api "Login missing password" POST "/auth/login" `
    @{ email = $AdminEmail; password = "" } `
    -Session $GuestSession -ExpectedStatus @(422) | Out-Null

Test-Api "Login wrong credentials" POST "/auth/login" `
    @{ email = $AdminEmail; password = "WrongPassword123" } `
    -Session $GuestSession -ExpectedStatus @(401) | Out-Null

# ============================================================
# 4. ADMIN LOGIN AND AUTHENTICATION
# ============================================================

Write-Host "`n========== ADMIN AUTHENTICATION ==========" -ForegroundColor Cyan

$AdminLogin = Test-Api "Admin login" POST "/auth/login" `
    @{ email = $AdminEmail; password = $AdminPassword } `
    -Session $AdminSession -ExpectedStatus @(200) -ExpectedSuccess $true

Test-Api "Admin /auth/me" GET "/auth/me" `
    -Session $AdminSession -ExpectedStatus @(200) -ExpectedSuccess $true | Out-Null

# ============================================================
# 5. CUSTOMER LOGIN AND AUTHORIZATION
# ============================================================

Write-Host "`n========== CUSTOMER AUTHENTICATION ==========" -ForegroundColor Cyan

$CustomerLogin = Test-Api "Customer login" POST "/auth/login" `
    @{ email = $CustomerEmail; password = $CustomerPassword } `
    -Session $CustomerSession -ExpectedStatus @(200) -ExpectedSuccess $true

Test-Api "Customer /auth/me" GET "/auth/me" `
    -Session $CustomerSession -ExpectedStatus @(200) -ExpectedSuccess $true | Out-Null

Test-Api "Customer forbidden from customers list" GET "/admin/customers" `
    -Session $CustomerSession -ExpectedStatus @(403) | Out-Null

Test-Api "Customer forbidden from admins list" GET "/admin/admins" `
    -Session $CustomerSession -ExpectedStatus @(403) | Out-Null

Test-Api "Customer forbidden from getting user" GET "/admin/users/1" `
    -Session $CustomerSession -ExpectedStatus @(403) | Out-Null

Test-Api "Customer forbidden from adding user" POST "/admin/users" `
    @{ username = "Forbidden"; email = "forbidden@test.com"; password = "Test@12345" } `
    -Session $CustomerSession -ExpectedStatus @(403) | Out-Null

Test-Api "Customer forbidden from adding category" POST "/admin/categories" `
    @{ categoryName = "Forbidden"; description = "Test"; urlImage = $null } `
    -Session $CustomerSession -ExpectedStatus @(403) | Out-Null

Test-Api "Customer forbidden from adding product" POST "/admin/products" `
    @{} -Session $CustomerSession -ExpectedStatus @(403) | Out-Null

# ============================================================
# 6. ADMIN USER ENDPOINTS
# ============================================================

Write-Host "`n========== ADMIN / USERS ==========" -ForegroundColor Cyan

Test-Api "Get customers" GET "/admin/customers" `
    -Session $AdminSession -ExpectedStatus @(200) -ExpectedSuccess $true | Out-Null

Test-Api "Get admins" GET "/admin/admins" `
    -Session $AdminSession -ExpectedStatus @(200) -ExpectedSuccess $true | Out-Null

Test-Api "Get nonexistent user" GET "/admin/users/$NotFoundID" `
    -Session $AdminSession -ExpectedStatus @(404) | Out-Null

Test-Api "Get invalid user ID" GET "/admin/users/abc" `
    -Session $AdminSession -ExpectedStatus @(400) | Out-Null

Test-Api "Add user with invalid email" POST "/admin/users" `
    @{
        role = "Customer"
        status = "Active"
        username = "InvalidEmail"
        email = "not-an-email"
        password = "Test@12345"
        phoneNumber = "0790000000"
    } -Session $AdminSession -ExpectedStatus @(422) | Out-Null

Test-Api "Add user with invalid role" POST "/admin/users" `
    @{
        role = "SuperAdmin"
        status = "Active"
        username = "InvalidRole"
        email = "${TestPrefix}_role@test.com"
        password = "Test@12345"
    } -Session $AdminSession -ExpectedStatus @(422) | Out-Null

$TestUserEmail = "${TestPrefix}_user@test.com"

$AddUser = Test-Api "Add temporary user" POST "/admin/users" `
    @{
        role = "Customer"
        status = "Active"
        username = "${TestPrefix}_User"
        email = $TestUserEmail
        password = "Test@12345"
        phoneNumber = "0790000000"
    } -Session $AdminSession -ExpectedStatus @(201) -ExpectedSuccess $true

$TestUserID = Get-ResponseID $AddUser @("userId", "UserID", "id")

if ($null -ne $TestUserID) {

    Test-Api "Get newly created user" GET "/admin/users/$TestUserID" `
        -Session $AdminSession -ExpectedStatus @(200) -ExpectedSuccess $true | Out-Null

    Test-Api "Update temporary user" PUT "/admin/users/$TestUserID" `
        @{
            role = "Customer"
            status = "Active"
            username = "${TestPrefix}_Updated"
            email = $TestUserEmail
            phoneNumber = "0781111111"
        } -Session $AdminSession -ExpectedStatus @(200) -ExpectedSuccess $true | Out-Null

    Test-Api "Deactivate temporary user" PATCH "/admin/users/$TestUserID/deactivate" `
        -Session $AdminSession -ExpectedStatus @(200) -ExpectedSuccess $true | Out-Null

    Test-Api "Activate temporary user" PATCH "/admin/users/$TestUserID/activate" `
        -Session $AdminSession -ExpectedStatus @(200) -ExpectedSuccess $true | Out-Null

    Test-Api "Change temporary user password" PATCH "/admin/users/$TestUserID/password" `
        @{ password = "NewTest@12345" } `
        -Session $AdminSession -ExpectedStatus @(200) -ExpectedSuccess $true | Out-Null

    Test-Api "Change password with invalid value" PATCH "/admin/users/$TestUserID/password" `
        @{ password = "123" } `
        -Session $AdminSession -ExpectedStatus @(422) | Out-Null

    Test-Api "Delete temporary user" DELETE "/admin/users/$TestUserID" `
        -Session $AdminSession -ExpectedStatus @(200) -ExpectedSuccess $true | Out-Null

    Test-Api "Deleted user should not be found" GET "/admin/users/$TestUserID" `
        -Session $AdminSession -ExpectedStatus @(404) | Out-Null
}
else {

    Write-Warning "User ID was not returned. User update/delete tests were skipped."
}

Test-Api "Delete nonexistent user" DELETE "/admin/users/$NotFoundID" `
    -Session $AdminSession -ExpectedStatus @(404) | Out-Null

# ============================================================
# 7. PUBLIC CATEGORY ENDPOINTS
# ============================================================

Write-Host "`n========== CATEGORIES / PUBLIC ==========" -ForegroundColor Cyan

Test-Api "Get all categories" GET "/categories" `
    -ExpectedStatus @(200) -ExpectedSuccess $true | Out-Null

Test-Api "Get nonexistent category" GET "/categories/$NotFoundID" `
    -ExpectedStatus @(404) | Out-Null

Test-Api "Get category with invalid ID" GET "/categories/abc" `
    -ExpectedStatus @(400) | Out-Null

# ============================================================
# 8. ADMIN CATEGORY ENDPOINTS
# ============================================================

Write-Host "`n========== CATEGORIES / ADMIN ==========" -ForegroundColor Cyan

$CategoryName = "${TestPrefix}_Category"

$AddCategory = Test-Api "Add category" POST "/admin/categories" `
    @{
        categoryName = $CategoryName
        description = "API integration test category"
        urlImage = $null
    } -Session $AdminSession -ExpectedStatus @(201) -ExpectedSuccess $true

$TestCategoryID = Get-ResponseID $AddCategory @("categoryId", "CategoryID", "id")

if ($null -ne $TestCategoryID) {

    Test-Api "Get newly created category" GET "/categories/$TestCategoryID" `
        -ExpectedStatus @(200) -ExpectedSuccess $true | Out-Null

    Test-Api "Update category" PUT "/admin/categories/$TestCategoryID" `
        @{
            categoryName = "${CategoryName}_Updated"
            description = "Updated API integration test category"
            urlImage = $null
        } -Session $AdminSession -ExpectedStatus @(200) -ExpectedSuccess $true | Out-Null

    Test-Api "Delete category" DELETE "/admin/categories/$TestCategoryID" `
        -Session $AdminSession -ExpectedStatus @(200) -ExpectedSuccess $true | Out-Null

    Test-Api "Deleted category should not be found" GET "/categories/$TestCategoryID" `
        -ExpectedStatus @(404) | Out-Null
}
else {

    Write-Warning "Category ID was not returned. Category update/delete tests were skipped."
}

Test-Api "Delete nonexistent category" DELETE "/admin/categories/$NotFoundID" `
    -Session $AdminSession -ExpectedStatus @(404) | Out-Null

# ============================================================
# 9. PUBLIC PRODUCT ENDPOINTS
# ============================================================

Write-Host "`n========== PRODUCTS / PUBLIC ==========" -ForegroundColor Cyan

Test-Api "Get all products" GET "/products" `
    -ExpectedStatus @(200) -ExpectedSuccess $true | Out-Null

Test-Api "Get nonexistent product" GET "/products/$NotFoundID" `
    -ExpectedStatus @(404) | Out-Null

Test-Api "Get product with invalid ID" GET "/products/abc" `
    -ExpectedStatus @(400) | Out-Null

Test-Api "Get products by nonexistent category" GET "/categories/$NotFoundID/products" `
    -ExpectedStatus @(200) -ExpectedSuccess $true | Out-Null

Test-Api "Get products by invalid category ID" GET "/categories/abc/products" `
    -ExpectedStatus @(400) | Out-Null

# ============================================================
# 10. ADMIN PRODUCT ENDPOINTS
# ============================================================

Write-Host "`n========== PRODUCTS / ADMIN ==========" -ForegroundColor Cyan

# Create a dedicated category for product tests.
$ProductCategoryName = "${TestPrefix}_ProductCategory"

$ProductCategory = Test-Api "Create category for product test" POST "/admin/categories" `
    @{
        categoryName = $ProductCategoryName
        description = "Temporary category for product tests"
        urlImage = $null
    } -Session $AdminSession -ExpectedStatus @(201) -ExpectedSuccess $true

$ProductCategoryID = Get-ResponseID $ProductCategory @("categoryId", "CategoryID", "id")

if ($null -ne $ProductCategoryID) {

    $ProductName = "${TestPrefix}_Product"

    Test-Api "Add product with invalid category" POST "/admin/products" `
        @{
            categoryId = $NotFoundID
            productName = "${ProductName}_InvalidCategory"
            description = "Invalid category test"
            price = "10.50"
            stock = 5
            isbn = "9780132350884"
            author = "Test Author"
            publisher = "Test Publisher"
            publishedYear = 2020
            pages = 100
        } -Session $AdminSession -ExpectedStatus @(400, 404, 422) | Out-Null

    Test-Api "Add product with invalid price" POST "/admin/products" `
        @{
            categoryId = $ProductCategoryID
            productName = "${ProductName}_InvalidPrice"
            description = "Invalid price test"
            price = "-10.50"
            stock = 5
            author = "Test Author"
            pages = 100
        } -Session $AdminSession -ExpectedStatus @(422) | Out-Null

    Test-Api "Add product with negative stock" POST "/admin/products" `
        @{
            categoryId = $ProductCategoryID
            productName = "${ProductName}_InvalidStock"
            description = "Invalid stock test"
            price = "10.50"
            stock = -1
            author = "Test Author"
            pages = 100
        } -Session $AdminSession -ExpectedStatus @(422) | Out-Null

    $AddProduct = Test-Api "Add product" POST "/admin/products" `
        @{
            categoryId = $ProductCategoryID
            productName = $ProductName
            description = "API integration test product"
            price = "19.99"
            stock = 25
            isbn = "9780132350884"
            author = "Test Author"
            publisher = "Test Publisher"
            publishedYear = 2020
            pages = 250
        } -Session $AdminSession -ExpectedStatus @(201) -ExpectedSuccess $true

    $TestProductID = Get-ResponseID $AddProduct @("productId", "ProductID", "id")

    if ($null -ne $TestProductID) {

        Test-Api "Get newly created product" GET "/products/$TestProductID" `
            -ExpectedStatus @(200) -ExpectedSuccess $true | Out-Null

        Test-Api "Get products by its category" GET "/categories/$ProductCategoryID/products" `
            -ExpectedStatus @(200) -ExpectedSuccess $true | Out-Null

        Test-Api "Update product" PUT "/admin/products/$TestProductID" `
            @{
                categoryId = $ProductCategoryID
                productName = "${ProductName}_Updated"
                description = "Updated API integration test product"
                price = "24.50"
                stock = 30
                isbn = "9780132350884"
                author = "Updated Test Author"
                publisher = "Updated Publisher"
                publishedYear = 2021
                pages = 300
            } -Session $AdminSession -ExpectedStatus @(200) -ExpectedSuccess $true | Out-Null

        Test-Api "Delete product" DELETE "/admin/products/$TestProductID" `
            -Session $AdminSession -ExpectedStatus @(200) -ExpectedSuccess $true | Out-Null

        Test-Api "Deleted product should not be found" GET "/products/$TestProductID" `
            -ExpectedStatus @(404) | Out-Null
    }
    else {

        Write-Warning "Product ID was not returned. Product update/delete tests were skipped."
    }

    # Product is deleted first, so its category can now be deleted.
    Test-Api "Delete temporary product category" DELETE "/admin/categories/$ProductCategoryID" `
        -Session $AdminSession -ExpectedStatus @(200) -ExpectedSuccess $true | Out-Null
}
else {

    Write-Warning "Product category ID was not returned. Product creation tests were skipped."
}

Test-Api "Delete nonexistent product" DELETE "/admin/products/$NotFoundID" `
    -Session $AdminSession -ExpectedStatus @(404) | Out-Null

# ============================================================
# 11. LOGOUT
# ============================================================

Write-Host "`n========== LOGOUT ==========" -ForegroundColor Cyan

Test-Api "Customer logout" POST "/auth/logout" `
    -Session $CustomerSession -ExpectedStatus @(200) -ExpectedSuccess $true | Out-Null

Test-Api "Customer session invalid after logout" GET "/auth/me" `
    -Session $CustomerSession -ExpectedStatus @(401) | Out-Null

Test-Api "Admin logout" POST "/auth/logout" `
    -Session $AdminSession -ExpectedStatus @(200) -ExpectedSuccess $true | Out-Null

Test-Api "Admin session invalid after logout" GET "/auth/me" `
    -Session $AdminSession -ExpectedStatus @(401) | Out-Null

# ============================================================
# 12. REPORT
# ============================================================

$Total = $Results.Count

$Passed = @(
    $Results | Where-Object { $_.Success }
).Count

$Failed = $Total - $Passed

$Percentage = if ($Total -gt 0) {
    [math]::Round(($Passed / $Total) * 100, 2)
}
else {
    0
}

# Export CSV
$Results | Export-Csv `
    -Path $CsvReport `
    -NoTypeInformation `
    -Encoding UTF8

# Build TXT report
$Report = @"
============================================================
                 AL-RAFF API TEST REPORT
============================================================

Date          : $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")
Base URL      : $BaseUrl

Total Tests   : $Total
Passed        : $Passed
Failed        : $Failed
Success Rate  : $Percentage %

------------------------------------------------------------
TEST DETAILS
------------------------------------------------------------
"@

foreach ($Item in $Results) {

    $Status = if ($Item.Success) {
        "PASS"
    }
    else {
        "FAIL"
    }

    $Report += @"

[$Status] $($Item.Test)
Method   : $($Item.Method)
Route    : $($Item.Route)
Expected : $($Item.Expected)
Actual   : $($Item.Actual)
Message  : $($Item.APIMessage)
Errors   : $($Item.Errors)
------------------------------------------------------------
"@
}

$Report += @"

============================================================
SUMMARY
============================================================
Total  : $Total
Passed : $Passed
Failed : $Failed
Rate   : $Percentage %
============================================================
"@

$Report | Out-File -FilePath $TxtReport -Encoding UTF8

# ============================================================
# FINAL OUTPUT
# ============================================================

Write-Host "`n==================================================" -ForegroundColor Cyan
Write-Host "              FINAL TEST REPORT"
Write-Host "==================================================" -ForegroundColor Cyan

Write-Host "Total Tests  : $Total"

Write-Host "Passed       : $Passed" -ForegroundColor Green

Write-Host "Failed       : $Failed" -ForegroundColor Red

Write-Host "Success Rate : $Percentage %"

Write-Host "`nCSV Report: $CsvReport" -ForegroundColor Yellow

Write-Host "TXT Report: $TxtReport" -ForegroundColor Yellow

if ($Failed -gt 0) {

    Write-Host "`nSome tests failed. Review the report." -ForegroundColor Red

}
else {

    Write-Host "`nAll executed tests passed!" -ForegroundColor Green

}
