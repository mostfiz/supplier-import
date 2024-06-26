<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ImportProductsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate');
    }

    public function testProcessRow()
    {
        $command = new ImportProducts();
        
        $validData = [
            'price' => '10',
            'stock_level' => '15',
            'discontinued' => 'no'
        ];
        $result = $command->processRow($validData);
        $this->assertFalse($result['skip']);
        $this->assertEquals('10', $result['data']['price']);
        $this->assertEquals('15', $result['data']['stock_level']);
        $this->assertArrayNotHasKey('discontinued_date', $result['data']);

        $discontinuedData = [
            'price' => '10',
            'stock_level' => '15',
            'discontinued' => 'yes'
        ];
        $result = $command->processRow($discontinuedData);
        $this->assertFalse($result['skip']);
        $this->assertEquals('10', $result['data']['price']);
        $this->assertEquals('15', $result['data']['stock_level']);
        $this->assertArrayHasKey('discontinued_date', $result['data']);

        $lowPriceLowStockData = [
            'price' => '4',
            'stock_level' => '9',
            'discontinued' => 'no'
        ];
        $result = $command->processRow($lowPriceLowStockData);
        $this->assertTrue($result['skip']);

        $highPriceData = [
            'price' => '1001',
            'stock_level' => '15',
            'discontinued' => 'no'
        ];
        $result = $command->processRow($highPriceData);
        $this->assertTrue($result['skip']);
    }

    public function testHandle()
    {
        $file = base_path('tests/Feature/stock.csv');
        $command = Mockery::mock(ImportProducts::class . '[option]');
        $command->shouldReceive('option')->with('test')->andReturn(false);

        $this->artisan('import:products', ['file' => $file, '--test' => true])
            ->expectsOutput('Processed: 3')
            ->expectsOutput('Successful: 0')
            ->expectsOutput('Skipped: 2')
            ->assertExitCode(0);

        // Check the database for correct insertion
        $this->assertDatabaseCount('products', 0);

        $this->artisan('import:products', ['file' => $file])
            ->expectsOutput('Processed: 3')
            ->expectsOutput('Successful: 1')
            ->expectsOutput('Skipped: 2')
            ->assertExitCode(0);

        $this->assertDatabaseCount('products', 1);
    }
}

