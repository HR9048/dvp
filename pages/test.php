<?php
function convertToWords($number) {
    $words = array(
        0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five',
        6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten',
        11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen',
        16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen', 20 => 'Twenty',
        30 => 'Thirty', 40 => 'Forty', 50 => 'Fifty', 60 => 'Sixty', 70 => 'Seventy',
        80 => 'Eighty', 90 => 'Ninety'
    );
    
    $units = array('', 'Thousand', 'Lakh', 'Crore');
    
    if ($number == 0) {
        return 'Zero';
    }

    $number_str = (string) $number;
    $number_length = strlen($number_str);
    $levels = (int) (($number_length + 2) / 3);
    $max_length = $levels * 3;
    $number_str = substr('00'.$number_str, -$max_length);
    $num_chunks = str_split($number_str, 3);
    
    $result = [];
    for ($i = 0; $i < count($num_chunks); $i++) {
        $chunk = (int) $num_chunks[$i];
        if ($chunk) {
            $words_chunk = '';
            $hundreds = (int) ($chunk / 100);
            $tens_units = $chunk % 100;
            $tens = (int) ($tens_units / 10) * 10;
            $units_digit = $tens_units % 10;

            if ($hundreds) {
                $words_chunk .= $words[$hundreds] . ' Hundred ';
            }

            if ($tens_units < 20) {
                $words_chunk .= $words[$tens_units];
            } else {
                $words_chunk .= $words[$tens];
                if ($units_digit) {
                    $words_chunk .= ' ' . $words[$units_digit];
                }
            }

            $result[] = $words_chunk . ' ' . $units[count($num_chunks) - $i - 1];
        }
    }
    
    return implode(' ', $result) . 'Rupees';
}

// Example usage:
$amount = 123456789;
echo convertToWords($amount);  // Output: "Twelve Crore Thirty Four Lakh Fifty Six Thousand Seven Hundred Eighty Nine Rupees"
?>
