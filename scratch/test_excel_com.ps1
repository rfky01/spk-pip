$filePath = "D:\KULIAH\Joki\Annisa\skripsi\BAB IV\daftar_pd-SMP TUNAS BANGSA-2026-10-17 05_49_36.xlsx"
$excel = New-Object -ComObject Excel.Application
$excel.Visible = $false
$excel.DisplayAlerts = $false

try {
    $wb = $excel.Workbooks.Open($filePath)
    Write-Output "EXCEL_OPEN_SUCCESS: Workbook '$($wb.Name)' opened successfully without error!"
    Write-Output "Sheet Name: $($wb.Sheets.Item(1).Name)"
    Write-Output "UsedRange rows: $($wb.Sheets.Item(1).UsedRange.Rows.Count)"
    $wb.Close($false)
} catch {
    Write-Output "EXCEL_OPEN_FAILED: $($_.Exception.Message)"
} finally {
    $excel.Quit()
    [System.Runtime.Interopservices.Marshal]::ReleaseComObject($excel) | Out-Null
}
