<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Brick\Money\Money;
use Brick\Math\RoundingMode;

$price1 = Money::of('10.500', 'JOD');
$price2 = Money::of('5.250', 'JOD');

// Addition
$total = $price1->plus($price2);

// Subtraction
$difference = $price1->minus($price2);

// Multiplication
$quantity = $total->multipliedBy(3);

// Division
$division = $price1->dividedBy(3, RoundingMode::HalfUp);

echo "Price 1: $price1\n";
echo "Price 2: $price2\n";
echo "Total: $total\n";
echo "Difference: $difference\n";
echo "Quantity: $quantity\n";
echo "Division: $division\n";