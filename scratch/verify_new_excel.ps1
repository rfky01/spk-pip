$filePath = "D:\KULIAH\Joki\Annisa\skripsi\BAB IV\Data_Calon_Penerima_PIP_SMP_TUNAS_BANGSA.xlsx"
$excel = New-Object -ComObject Excel.Application
$excel.Visible = $false
$excel.DisplayAlerts = $false

try {
    $wb = $excel.Workbooks.Open($filePath)
    Write-Output "EXCEL_VERIFIED: Workbook '$($wb.Name)' opens smoothly and cleanly!"
    $ws = $wb.Sheets.Item(1)
    Write-Output "Sheet Name: $($ws.Name)"
    Write-Output "Total Rows: $($ws.UsedRange.Rows.Count)"
    Write-Output "Total Columns: $($ws.UsedRange.Columns.Count)"
    for ($i = 5; $i -le 14; $i++) {
        $no = $ws.Cells.Item($i, 1).Text
        $nisn = $ws.Cells.Item($i, 2).Text
        $nama = $ws.Cells.Item($i, 3).Text
        $hp = $ws.Cells.Item($i, 4).Text
        $c1 = $ws.Cells.Item($i, 9).Text
        Write-Output "$no. $nama | NISN: $nisn | HP: $hp | Penghasilan: $c1"
    }
    $wb.Close($false)
} catch {
    Write-Output "ERROR: $($_.Exception.Message)"
} finally {
    $excel.Quit()
    [System.Runtime.Interopservices.Marshal]::ReleaseComObject($excel) | Out-Null
}
