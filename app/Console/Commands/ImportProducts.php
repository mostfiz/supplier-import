<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ImportProducts extends Command
{
    protected $signature = 'import:products {file} {--test} {--env=}';
    protected $description = 'Import products from a CSV file';

    // Define the mapping between CSV headers and database field names
    protected $headerToDbFieldMapping = [
        'Product Name' => 'product_name',
        'Product Description' => 'product_desc',
        'Product Code' => 'product_code',
        'Stock' => 'stock_level',
        'Cost in GBP' => 'price',
        'Discontinued_date' => 'discontinued_date',
        // Add other mappings as needed
    ];

    public function handle()
    {
        $file = $this->argument('file');
        $isTestMode = $this->option('test');
        $environment = $this->option('env');

        if (!file_exists($file)) {
            $this->error('File does not exist.');
            return;
        }

        $handle = fopen($file, 'r');
        if ($handle === false) {
            $this->error('Failed to open file.');
            return;
        }

        $headers = fgetcsv($handle, 1000, ',');
        if ($headers === false) {
            $this->error('Failed to read headers.');
            fclose($handle);
            return;
        }

        $processed = $successful = $skipped = 0;
        $failedItems = [];

        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            $processed++;

            if (count($row) !== count($headers)) {
                $this->error("Row $processed has incorrect number of columns.");
                $failedItems[] = $row;
                continue;
            }

            $data = array_combine($headers, $row);
            
            // Handle null and empty values
            $data = $this->handleNullValues($data);

            // Validate and apply business rules
            $result = $this->processRow($data);

            if ($result['skip']) {
                $skipped++;
                continue;
            }else{
                try {
                    DB::table('products')->insert($result['data']);
                    $successful++;
                } catch (\Exception $e) {
                    print_r($e->getMessage());
                    $failedItems[] = $data;
                }
            }
            
            // if (!$isTestMode) {
                
            // }
        }

        fclose($handle);

        $this->info("Processed: $processed");
        $this->info("Successful: $successful");
        $this->info("Skipped: $skipped");
        if (!empty($failedItems)) {
            $this->info("Failed Items: " . count($failedItems));
        }
        
        if ($environment) {
            $this->info("Running in $environment environment.");
        }
    }

    protected function processRow(array $data)
    {
        $price = (float) $data['Cost in GBP'];
        $stockLevel = (int) $data['Stock'];
        $discontinued = strtolower($data['Discontinued']) === 'yes';

        if (($price < 5 && $stockLevel < 10) || $price > 1000) {
            return ['skip' => true];
        }

        if ($discontinued) {
            $data['Discontinued_date'] = Carbon::now();
        }
        unset($data['Discontinued']);
        $data['created_at'] = now(); // Manually setting created_at
        $data['updated_at'] = now();
        // Transform data using the mapping
        
        $data = $this->transformDataUsingMapping($data);
        return ['skip' => false, 'data' => $data];
    }

    protected function handleNullValues(array $data)
    {
        $defaults = [
            'Cost in GBP' => 0,
            'Stock' => 0,
            'Discontinued' => 'no',
            'Discontinued_date' => null,
        ];

        foreach ($defaults as $field => $defaultValue) {
            if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
                $data[$field] = $defaultValue;
            }
        }

        return $data;
    }

    protected function transformDataUsingMapping(array $data)
    {
        $transformedData = [];

        foreach ($this->headerToDbFieldMapping as $csvHeader => $dbField) {
            if (isset($data[$csvHeader])) {
                $transformedData[$dbField] = $data[$csvHeader];
            } else {
                $transformedData[$dbField] = null; // or some default value
            }
        }

        return $transformedData;
    }
}
