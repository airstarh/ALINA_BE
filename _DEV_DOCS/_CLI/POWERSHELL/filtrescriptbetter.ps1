# Путь к файлу cpuz_info.txt
$cpuzInfoFilePath = Join-Path -Path $PSScriptRoot -ChildPath "cpuz_info.txt.txt"

# Путь к файлу для сохранения данных
$filteredFilePath = Join-Path -Path $PSScriptRoot -ChildPath "filteredbetter.txt"

# Проверить, существует ли файл cpuz_info.txt
if (Test-Path $cpuzInfoFilePath) {
    # Создать пустой массив для хранения данных
    $data = @()
# Get the internal (local) IP address
$InternalIp = (Get-NetIPAddress -AddressFamily IPv4 | Where-Object { $_.InterfaceAlias -eq "Ethernet" }).IPAddress
$data += "Internal Ip: $InternalIp"

# Get the public IP address
$PublicIp = (Invoke-RestMethod -Uri "http://ipinfo.io/json").ip
$data += "Public Ip: $PublicIp"

# Получить MAC-адрес Wi-Fi адаптера
$WifiMac = (Get-WmiObject Win32_NetworkAdapterConfiguration | Where-Object { $_.Description -like '*Wi-Fi*' }).MACAddress

# Получить MAC-адрес Ethernet адаптера
$EthernetMac = (Get-WmiObject Win32_NetworkAdapterConfiguration | Where-Object { $_.Description -like '*Ethernet*' }).MACAddress

# Создать строку данных, содержащую MAC-адреса
$data += "WifiMac: $WifiMac"

$data += "EthernetMac: $EthernetMac"


    
    $inProcessorsInformation = $false
    $inMemorySPD = $false
    $inStorage = $false
    $inDisplayAdapters = $false

    # Считать строки из файла cpuz_info.txt
    $lines = Get-Content $cpuzInfoFilePath

    # Пройти по каждой строке
    foreach ($line in $lines) {
        # Если строка соответствует разделу "Processors Information", установить соответствующий флаг
        if ($line -match "Processors Information") {
	$data += $line
            Write-Host "Entering Processors Information section"
            $inProcessorsInformation = $true
            $inMemorySPD = $false
            $inStorage = $false
            $inDisplayAdapters = $false
        }
        # Если строка соответствует разделу "Memory SPD", установить соответствующий флаг
        elseif ($line -match "Memory SPD") {
$data += $line
            Write-Host "Entering Memory SPD section"
            $inProcessorsInformation = $false
            $inMemorySPD = $true
            $inStorage = $false
            $inDisplayAdapters = $false
        }
        # Если строка соответствует разделу "Storage", установить соответствующий флаг
        elseif ($line -match "Storage") {
$data += $line
            Write-Host "Entering Storage section"
            $inProcessorsInformation = $false
            $inMemorySPD = $false
            $inStorage = $true
            $inDisplayAdapters = $false
        }
        # Если строка соответствует разделу "Display Adapters", установить соответствующий флаг
        elseif ($line -match "Display Adapters") {
$data += $line
            Write-Host "Entering Display Adapters section"
            $inProcessorsInformation = $false
            $inMemorySPD = $false
            $inStorage = $false
            $inDisplayAdapters = $true
        }
        # Если флаг установлен для раздела "Processors Information", извлечь интересующие данные
        elseif ($inProcessorsInformation) {
            if ($line -match "Number of cores\s") {
                $data += $line
                Write-Host "Extracted Number of cores: $($matches[1])"
            }
            elseif ($line -match "Number of threads\s") {
                $data += $line
                Write-Host "Extracted Number of threads: $($matches[1])"
            }
            elseif ($line -match "Specification\s") {
                $data += $line
                Write-Host "Extracted Specification: $($matches[1])"
            }
            elseif ($line -match "Package\s") {
                $data += $line
                Write-Host "Extracted Package: $($matches[1])"
            }
        }
        # Если флаг установлен для раздела "Memory SPD", извлечь интересующие данные
        elseif ($inMemorySPD) {
            if ($line -match "Memory type\s") {
                $data += $line
                Write-Host "Extracted Memory type: $($matches[1])"
            }
            elseif ($line -match "Module format\s") {
                $data += $line
                Write-Host "Extracted Module format: $($matches[1])"
            }
        }
        # Если флаг установлен для раздела "Storage", извлечь интересующие данные
        elseif ($inStorage) {
            if ($line -match "Name\s") {
                $data += $line
                Write-Host "Extracted Name: $($matches[1])"
            }
            elseif ($line -match "Capacity\s") {
                $data += $line
                Write-Host "Extracted Capacity: $($matches[1])"
            }
            elseif ($line -match "Bus Type\s") {
                $data += $line
                Write-Host "Extracted Bus Type: $($matches[1])"
            }
        }
        # Если флаг установлен для раздела "Display Adapters", извлечь интересующие данные
        elseif ($inDisplayAdapters) {
            if ($line -match "Name\s") {
                $data += $line
                Write-Host "Extracted Name: $($matches[1])"
            }
            elseif ($line -match "Memory size\s") {
                $data += $line
                Write-Host "Extracted Memory size: $($matches[1])"
            }
        }
    }

    # Вывести полученные данные в файл
    $data | Out-File -FilePath $filteredFilePath
    Write-Host "data saved $filteredFilePath"
} else {
    Write-Host "file cpuz_info.txt not found."
}
