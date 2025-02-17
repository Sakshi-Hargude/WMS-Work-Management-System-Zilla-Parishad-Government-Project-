<?php
// app/Helpers/CommonHelper.php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

//The class we cann access for the whole project if in project common functions needed
class CommonHelper
{


    //* Formats a given integer as an ordinal number string.
    public static function formatTItemNo($t_item_no)
    {
        // Check if the last digit of the number is 1 and the number itself is not 11
        if ($t_item_no % 10 == 1 && $t_item_no != 11) {
            return '1st';
        }
         // Check if the last digit of the number is 2 and the number itself is not 12
        elseif ($t_item_no % 10 == 2 && $t_item_no != 12) {
            return '2nd';
        }
         // Check if the last digit of the number is 3 and the number itself is not 13
        elseif ($t_item_no % 10 == 3 && $t_item_no != 13) {
            return '3rd';
        }
         // Check if the number is 4 or greater
        elseif ($t_item_no >= 4) {
            return $t_item_no . 'th';// Return the number with 'th' appended for numbers 4 and above
        }
        else {
            return $t_item_no;// Return the number itself if it does not meet any of the above conditions
        }
    }




        /**
         * Returns the type of bill based on the given final bill status.
         *
         * @param int $final_bill The status of the bill (1 for final bill, any other value for R.A. bill).
         * @return string The type of bill as a string.
         */
        public static function getBillType($final_bill)
        {
            // Use a ternary operator to return the bill type based on the final bill status
            return $final_bill == 1 ? '& Final Bill' : 'R.A. Bill';
            // If $final_bill is 1, return '& Final Bill'
            // Otherwise, return 'R.A. Bill'
        }



            /**
     * Formats a given integer as an ordinal number string.
     *
     * @param int $RBBillNo The number to be formatted.
     * @return string The formatted ordinal number.
     */

    public static function formatNumbers($RBBillNo)
    {

        // Check if the number is exactly 1
        if ($RBBillNo == 1) {
            return '1st';
        }
         // Check if the number is exactly 2
        elseif ($RBBillNo == 2) {
            return '2nd';
        }
         // Check if the number is exactly 3
        elseif ($RBBillNo == 3 ) {
            return '3rd';
        }
        // Check if the number is 4 or greater
        elseif ($RBBillNo >= 4) {
            return $RBBillNo . 'th';
        }
        else {
            return $RBBillNo;
        }

    }


    /**
 * Custom rounding function that rounds the given value to the nearest integer
 * and formats it to two decimal places without thousands separators.
 *
 * @param float $value The value to be rounded.
 * @return string The rounded value formatted to two decimal places.
 */

    function customRound($value) {
        // Separate the integer part and the decimal part
        $integerPart = floor($value);
        $decimalPart = $value - $integerPart;

        // Round the decimal part
        if ($decimalPart >= 0.5) {
            $roundedDecimalPart = 1;
        } else {
            $roundedDecimalPart = 0;
        }

        // Combine the integer and rounded decimal parts
        $roundedValue = $integerPart + $roundedDecimalPart;

// Format the rounded value to two decimal places without thousands separators
    return number_format($roundedValue, 2, '.', '');

}


// function convertAmountToWords($amount)
// {
//     $units = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine'];
//     $teens = ['', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
//     $tens = ['', 'Ten', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
//     $thousands = ['', 'Thousand', 'Lakh', 'Million', 'Billion', 'Trillion'];

//     $num = number_format($amount, 2, '.', '');
//     list($dollars, $cents) = explode('.', $num);

//     $dollars = intval($dollars);
//     $words = [];

//     if ($dollars == 0) {
//         $words[] = 'Zero';
//     } else {
//         $groups = array_reverse(str_split(str_pad($dollars, ceil(strlen($dollars) / 3) * 3, '0', STR_PAD_LEFT), 3));
//         foreach ($groups as $groupIndex => $group) {
//             $group = intval($group);
//             if ($group > 0) {
//                 $groupWords = [];
//                 $hundreds = floor($group / 100);
//                 $remainder = $group % 100;

//                 if ($hundreds > 0) {
//                     $groupWords[] = $units[$hundreds] . ' Hundred';
//                 }

//                 if ($remainder > 0) {
//                     if ($remainder < 10) {
//                         $groupWords[] = $units[$remainder];
//                     } elseif ($remainder < 20) {
//                         $groupWords[] = $teens[$remainder - 10];
//                     } else {
//                         $tensDigit = floor($remainder / 10);
//                         $unitDigit = $remainder % 10;
//                         if ($tensDigit > 0) {
//                             $groupWords[] = $tens[$tensDigit];
//                         }
//                         if ($unitDigit > 0) {
//                             $groupWords[] = $units[$unitDigit];
//                         }
//                     }
//                 }

//                 $groupWords[] = $thousands[$groupIndex];
//                 $words = array_merge($groupWords, $words);
//             }
//         }
//     }

//     $result = implode(' ', array_filter($words));
//     $result = ucfirst($result);

//     if ($cents > 0) {
//         $cents = intval($cents);
//         $centsWord=$this->convertAmountToWords($cents);
//         $result .= " and Paise $cents";
//     } else {
//         $result .= " and Paise";
//     }

//     return $result;
// }


// * Converts a numeric amount into its words representation.
public static function convertAmountToWords($fig)
{
     // Check if the input is empty, not numeric, or exceeds the maximum allowed value
     if (empty($fig) || !is_numeric($fig) || ($fig > 999999999.99)) {
        return "";
    }
    // Handle the case where the amount is zero
    if ($fig == 0) {
        return "रुपये शून्य आणि पैसे शून्य केवळ";
    }
    // Round the figure to two decimal places
    $fig = round($fig, 2);

    // Initialize variables
    $word = "रुपये ";  // Start with the Rupees prefix
    $lnFInt = intval($fig);  // Get the integer part of the figure
    $lnDcml = round(($fig - $lnFInt) * 100, 0);  // Calculate the decimal part in paise
    $lnFInt1 = $lnFInt;  // Store the integer part in another variable

    // Convert Crores
    if ($lnFInt1 > 9999999 && $lnFInt1 <= 999999999) {
        $lnNum = intval($lnFInt1 / 10000000);
        $word .= DB::table('f2w')->where('fig', $lnNum)->value('wrdm') . " कोटी ";
        $lnFInt1 -= ($lnNum * 10000000);
    }

    // Convert Lakhs
    if ($lnFInt1 > 99999 && $lnFInt1 <= 9999999) {
        $lnNum = intval($lnFInt1 / 100000);
        $word .= DB::table('f2w')->where('fig', $lnNum)->value('wrdm') . " लाख ";
        $lnFInt1 -= ($lnNum * 100000);
    }

    // Convert Thousands
    if ($lnFInt1 > 999 && $lnFInt1 <= 99999) {
        $lnNum = intval($lnFInt1 / 1000);
        $word .= DB::table('f2w')->where('fig', $lnNum)->value('wrdm') . " हजार ";
        $lnFInt1 -= ($lnNum * 1000);
    }

    // Convert Hundreds
    if ($lnFInt1 > 99 && $lnFInt1 <= 999) {
        $lnNum = intval($lnFInt1 / 100);
        $word .= DB::table('f2w')->where('fig', $lnNum)->value('wrdm') . "शे "; //शंभर // शे
        $lnFInt1 -= ($lnNum * 100);
    }

    // Convert remaining numbers below 100
    if ($lnFInt1 > 0 && $lnFInt1 <= 99) {
        $word .= DB::table('f2w')->where('fig', $lnFInt1)->value('wrdm');
    }

    $word .= " आणि पैसे ";

    // Convert decimal part in paise
    if ($lnDcml > 0) {
        $word .= DB::table('f2w')->where('fig', $lnDcml)->value('wrdm');
    } else {
        $word .= "शून्य";
    }

    $word .= " केवळ.";
    return $word;
}




//* Generates an HTML table summarizing the deduction details for a specific bill.
public static function DeductionSummaryDetails($tbillid)
{
     // Retrieve the bill details using the provided bill ID
    // dd($tbillid);
    $sammarydata=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
    // dd($sammarydata);

     // Extract the net amount and cheque amount from the bill details
    $C_netAmt= $sammarydata->c_netamt;
    $chqAmt= $sammarydata->chq_amt;

     // Convert the net amount to words
    $amountInWords = self::convertAmountToWords($C_netAmt);
    // dd($amountInWords);


     // Retrieve deduction percentages from the dedmasters table
    $SecDepositepc = DB::table('dedmasters')->where('Ded_M_Id', 2)->value('Ded_pc') ?: '';
    $CGSTpc = DB::table('dedmasters')->where('Ded_M_Id', 3)->value('Ded_pc') ?: '';
    $SGSTpc = DB::table('dedmasters')->where('Ded_M_Id', 4)->value('Ded_pc') ?: '';
    $Incomepc = DB::table('dedmasters')->where('Ded_M_Id', 5)->value('Ded_pc') ?: '';
    $Insurancepc = DB::table('dedmasters')->where('Ded_M_Id', 7)->value('Ded_pc') ?: '';
    $Labourpc = DB::table('dedmasters')->where('Ded_M_Id', 8)->value('Ded_pc') ?: '';
    $AdditionalSDpc = DB::table('dedmasters')->where('Ded_M_Id', 9)->value('Ded_pc') ?: '';
    $Royaltypc = DB::table('dedmasters')->where('Ded_M_Id', 10)->value('Ded_pc') ?: '';
    $finepc = DB::table('dedmasters')->where('Ded_M_Id', 11)->value('Ded_pc') ?: '';
    $Recoverypc = DB::table('dedmasters')->where('Ded_M_Id', 13)->value('Ded_pc') ?: '';

       // Format percentages, if value is 0, assign an empty string
        $SecDepositepc = $SecDepositepc != 0 ? $SecDepositepc . '%' : '';
        $CGSTpc = $CGSTpc != 0 ? $CGSTpc . '%' : '';
        $SGSTpc = $SGSTpc != 0 ? $SGSTpc . '%' : '';
        $Incomepc = $Incomepc != 0 ? $Incomepc . '%' : '';
        $Insurancepc = $Insurancepc != 0 ? $Insurancepc . '%' : '';
        $Labourpc = $Labourpc != 0 ? $Labourpc . '%' : '';
        $AdditionalSDpc = $AdditionalSDpc != 0 ? $AdditionalSDpc . '%' : '';
        $Royaltypc = $Royaltypc != 0 ? $Royaltypc . '%' : '';
        $finepc = $finepc != 0 ? $finepc . '%' : '';
        $Recoverypc = $Recoverypc != 0 ? $Recoverypc . '%' : '';


    // Retrieve deduction amounts from the billdeds table
    $deductionAmount=DB::table('billdeds')->where('T_Bill_Id' ,$tbillid)->get();
    // dd($deductionAmount);
    $additionalSDAmt=DB::table('billdeds')->where('T_Bill_Id' ,$tbillid)->where('Ded_Head','Additional S.D')->value('Ded_Amt');
    $additionalSDAmt = $additionalSDAmt ? $additionalSDAmt : '0.00';
    // dd($additionalSDAmt);
    $Security=DB::table('billdeds')
    ->where('T_Bill_Id' ,$tbillid)
    ->where('Ded_Head','Security Deposite')
    ->value('Ded_Amt');
    $Security = $Security ? $Security : '0.00';
    // dd($Security);
    $Income=DB::table('billdeds')
    ->where('T_Bill_Id' ,$tbillid)
    ->where('Ded_Head','Income Tax')
    ->value('Ded_Amt');
    $Income = $Income ? $Income : '0.00';
    // dd($Income);
    $CGST=DB::table('billdeds')
    ->where('T_Bill_Id' ,$tbillid)
    ->where('Ded_Head','CGST')
    ->value('Ded_Amt');
    $CGST = $CGST ? $CGST : '0.00';
    // dd($CGST);
    $SGST=DB::table('billdeds')
    ->where('T_Bill_Id' ,$tbillid)
    ->where('Ded_Head','SGST')
    ->value('Ded_Amt');
    $SGST = $SGST ? $SGST : '0.00';
    // dd($SGST);
    $Insurance=DB::table('billdeds')
    ->where('T_Bill_Id' ,$tbillid)
    ->where('Ded_Head','Work Insurance')
    ->value('Ded_Amt');
    $Insurance = $Insurance ? $Insurance : '0.00';
    // dd($Insurance);
    $Labour=DB::table('billdeds')
    ->where('T_Bill_Id' ,$tbillid)
    ->where('Ded_Head','Labour cess')
    ->value('Ded_Amt');
    $Labour = $Labour ? $Labour : '0.00';
    // dd($Labour);
    $Royalty=DB::table('billdeds')
    ->where('T_Bill_Id' ,$tbillid)
    ->where('Ded_Head','Royalty Charges')
    ->value('Ded_Amt');
    $Royalty = $Royalty ? $Royalty : '0.00';
    // dd($Royalty);
    $fine=DB::table('billdeds')
    ->where('T_Bill_Id' ,$tbillid)
    ->where('Ded_Head','fine')
    ->value('Ded_Amt');
    $fine = $fine ? $fine : '0.00';
    // dd($fine);
    $Recovery=DB::table('billdeds')
    ->where('T_Bill_Id' ,$tbillid)
    ->where('Ded_Head','Audit Recovery')
    ->value('Ded_Amt');
    $Recovery = $Recovery ? $Recovery : '0.00';
    // dd($Recovery);

       // Initialize HTML for deduction summary table
    $htmlDeduction='';
    $htmlDeduction .= '<div style="text-align: center; margin-top: 20px;">';
    $htmlDeduction .= '<table style="border: 1px solid black; border-collapse: collapse; margin: auto;">';
    $htmlDeduction .= '<thead>';
    $htmlDeduction .= '<tr>'; // Open a table row within the thead section
    $htmlDeduction .= '<th style="border: 1px solid black; padding: 8px;">Amount</th>';
    $htmlDeduction .= '<th style="border: 1px solid black; padding: 8px;">Details</th>';
    $htmlDeduction .= '</tr>'; // Close the table row within the thead section
    $htmlDeduction .= '</thead>';
    $htmlDeduction .= '<tbody>';

      // Add rows for each deduction type
    $htmlDeduction .='<tr >';
    $htmlDeduction .= '<td style="border: 1px solid black; padding: 8px;text-align:right;"> ' . self::formatIndianRupees($additionalSDAmt).'</td>';
    $htmlDeduction .='<td style="border: 1px solid black; padding: 8px;text-align:left;">Additional S.D: &nbsp;&nbsp;&nbsp; '.$AdditionalSDpc.'</td>';
    $htmlDeduction .='</tr>';
    $htmlDeduction .='<tr >';
    $htmlDeduction .='<td style="border: 1px solid black; padding: 8px;text-align:right;"> ' . self::formatIndianRupees($Security) .'</td>';
    $htmlDeduction .='<td style="border: 1px solid black; padding: 8px;">Security Deposite: &nbsp;&nbsp;&nbsp; '.$SecDepositepc.'</td>';
    $htmlDeduction .='</tr>';
    $htmlDeduction .='<tr >';
    $htmlDeduction .='<td style="border: 1px solid black; padding: 8px;text-align:right;"> ' . self::formatIndianRupees($Insurance) .'</td>';
    $htmlDeduction .='<td style="border: 1px solid black; padding: 8px;text-align:left;">Insurance: &nbsp;&nbsp;&nbsp; '.$Insurancepc.'</td>';
    $htmlDeduction .='</tr>';
    $htmlDeduction .='<tr >';
    $htmlDeduction .='<td style="border: 1px solid black; padding: 8px;text-align:right;"> ' . self::formatIndianRupees($Labour) .'</td>';
    $htmlDeduction .='<td style="border: 1px solid black; padding: 8px;text-align:left;">Labour Cess: &nbsp;&nbsp;&nbsp; '. $Labourpc.'</td>';
    $htmlDeduction .='</tr>';
    $htmlDeduction .='<tr >';
    $htmlDeduction .='<td style="border: 1px solid black; padding: 8px;text-align:right;"> ' . self::formatIndianRupees($Income) .'</td>';
    $htmlDeduction .='<td style="border: 1px solid black; padding: 8px;text-align:left;">Income Tax: &nbsp;&nbsp;&nbsp; '. $Incomepc.'</td>';
    $htmlDeduction .='</tr>';
    $htmlDeduction .='<tr >';
    $htmlDeduction .='<td style="border: 1px solid black; padding: 8px;text-align:right;"> ' . self::formatIndianRupees($CGST) .'</td>';
    $htmlDeduction .='<td style="border: 1px solid black; padding: 8px;text-align:left;">CGST: &nbsp;&nbsp;&nbsp; '.$CGSTpc.'</td>';
    $htmlDeduction .='</tr>';
    $htmlDeduction .='<tr >';
    $htmlDeduction .='<td style="border: 1px solid black; padding: 8px;text-align:right;"> ' . self::formatIndianRupees($SGST) .'</td>';
    $htmlDeduction .='<td style="border: 1px solid black; padding: 8px;text-align:left;">SGST: &nbsp;&nbsp;&nbsp; '. $SGSTpc.'</td>';
    $htmlDeduction .='</tr>';
    $htmlDeduction .='<tr >';
    $htmlDeduction .='<td style="border: 1px solid black; padding: 8px;text-align:right;"> ' . self::formatIndianRupees($Royalty) .'</td>';
    $htmlDeduction .='<td style="border: 1px solid black; padding: 8px;text-align:left;">Royalty:  charges &nbsp;&nbsp;&nbsp;'. $Royaltypc.'</td>';
    $htmlDeduction .='</tr>';
    $htmlDeduction .='<tr >';
    $htmlDeduction .='<td style="border: 1px solid black; padding: 8px;text-align:right;"> ' . self::formatIndianRupees($fine) .'</td>';
    $htmlDeduction .='<td style="border: 1px solid black; padding: 8px;text-align:left;">Fine:  &nbsp;&nbsp;&nbsp; '. $finepc.'</td>';
    $htmlDeduction .='</tr>';
    $htmlDeduction .='<tr >';
    $htmlDeduction .='<td style="border: 1px solid black; padding: 8px;text-align:right;"> ' . self::formatIndianRupees($Recovery) .'</td>';
    $htmlDeduction .='<td style="border: 1px solid black; padding: 8px;text-align:left;">Audit Recovery:  &nbsp;&nbsp;&nbsp; '. $Recoverypc.'</td>';
    $htmlDeduction .='</tr>';

    $htmlDeduction .='<tr>';
    $htmlDeduction .='<td style="border: 1px solid black; padding: 8px;text-align:right;"> '. self::formatIndianRupees($chqAmt).' </td>';
    $htmlDeduction .='<td style="border: 1px solid black; padding: 8px;text-align:left;">Cheque Amount &nbsp;&nbsp;&nbsp;  </td>';
    $htmlDeduction .='</tr>';
    $htmlDeduction .='<tr>';
    $htmlDeduction .='<td style="border: 1px solid black; padding: 8px;text-align:right; "> '. self::formatIndianRupees($C_netAmt).' </td>';
    $htmlDeduction .='<td style="border: 1px solid black; padding: 8px;text-align:left;">Total &nbsp;&nbsp;&nbsp;</td>';

    $htmlDeduction .='</tr>';


    $htmlDeduction .= '</tbody>';
    $htmlDeduction .= '</table>';
    $htmlDeduction .= '</div>';
    return $htmlDeduction;
}





//All amounts in convert in indian rupees format
// public static function formatIndianRupees($amount)
// {

//      // Check if the amount is zero
//     if ($amount == 0) {
//         return '0.00';
//     }

//     // Split the amount into integer and fractional parts
//     $parts = explode('.', number_format((float)$amount, 2, '.', ''));
//     $integerPart = (int)$parts[0];
//     $fractionalPart = isset($parts[1]) ? '.' . $parts[1] : '';

//     $crore = floor($integerPart / 10000000);
//     $integerPart %= 10000000;
//     $lakh = floor($integerPart / 100000);
//     $integerPart %= 100000;
//     $thousand = floor($integerPart / 1000);
//     $integerPart %= 1000;
//     $hundred = $integerPart;

//     $formatted = '';

//     if ($crore > 0) {
//         $formatted .= $crore . ',';
//     }

//     // Append lakh part, ensuring it's always two digits if it follows crore
//     if ($crore > 0) {
//         $formatted .= sprintf('%02d', $lakh) . ',';
//     } else {
//         $formatted .= $lakh > 0 ? $lakh . ',' : '';
//     }

//     // Append thousand part, ensuring it's always two digits if it follows lakh or crore
//     if ($crore > 0 || $lakh > 0) {
//         $formatted .= sprintf('%02d', $thousand) . ',';
//     } else {
//         $formatted .= $thousand > 0 ? $thousand . ',' : '';
//     }

//     // Append the hundreds part
//     if ($crore > 0 || $lakh > 0 || $thousand > 0) {
//         // Maintain leading zeros if thousands segment exists
//         $formatted .= sprintf('%03d', $hundred);
//     } else {
//         // Remove leading zeros if only hundreds segment exists
//         $formatted .= $hundred;
//     }

//     // Remove any trailing comma
//     $formatted = rtrim($formatted, ',');

//     // Append the fractional part if it exists
//     $formatted .= $fractionalPart;

//     return $formatted;
// }



//All amounts in convert in indian rupees format
public static function formatIndianRupees($amount)
{

     // Convert the amount to a float, handling invalid inputs gracefully
     $amount = floatval($amount);
    // Check if the amount is zero
    if ($amount == 0) {
        return '0.00';
    }

    // Check if the amount is negative
    $isNegative = $amount < 0;

    // Convert amount to positive for formatting
    $amount = abs($amount);

    // Split the amount into integer and fractional parts
    $parts = explode('.', number_format((float)$amount, 2, '.', ''));
    $integerPart = (int)$parts[0];
    $fractionalPart = isset($parts[1]) ? '.' . $parts[1] : '';

    $crore = floor($integerPart / 10000000);
    $integerPart %= 10000000;
    $lakh = floor($integerPart / 100000);
    $integerPart %= 100000;
    $thousand = floor($integerPart / 1000);
    $integerPart %= 1000;
    $hundred = $integerPart;

    $formatted = '';

    if ($crore > 0) {
        $formatted .= $crore . ',';
    }

    // Append lakh part, ensuring it's always two digits if it follows crore
    if ($crore > 0) {
        $formatted .= sprintf('%02d', $lakh) . ',';
    } else {
        $formatted .= $lakh > 0 ? $lakh . ',' : '';
    }

    // Append thousand part, ensuring it's always two digits if it follows lakh or crore
    if ($crore > 0 || $lakh > 0) {
        $formatted .= sprintf('%02d', $thousand) . ',';
    } else {
        $formatted .= $thousand > 0 ? $thousand . ',' : '';
    }

    // Append the hundreds part
    if ($crore > 0 || $lakh > 0 || $thousand > 0) {
        // Maintain leading zeros if thousands segment exists
        $formatted .= sprintf('%03d', $hundred);
    } else {
        // Remove leading zeros if only hundreds segment exists
        $formatted .= $hundred;
    }

    // Remove any trailing comma
    $formatted = rtrim($formatted, ',');

    // Append the fractional part if it exists
    $formatted .= $fractionalPart;

    // Add negative sign back if the amount was negative
    if ($isNegative) {
        $formatted = '-' . $formatted;
    }

    return $formatted;
}



}
