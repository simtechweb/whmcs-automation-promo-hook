<?php

/**

* Hook for automatically applying a promo code to orders with a .gr domain and hosting package.

* This hook is activated during the checkout process.

* It checks whether the order includes a .gr domain and hosting package.

* If so, it automatically applies the offer code to the cart and displays a confirmation message.

* @package WHMCS Hook

* @version 1.0.1

* @author Sim-Tech <info@simtech.gr>

* @link https://www.simtech.gr

* @since 1.0.0

*/



if (!defined("WHMCS")) {

    die("This file cannot be accessed directly");

}



use WHMCS\Database\Capsule;



add_hook('CartTotalAdjustment', 1, function($vars) {

    $cart_adjustments = array();



    $hasDomain = false;

    $hasHosting = false;



   // Check for domain registration

    foreach ($vars['domains'] as $domain) {

        if ($domain['type'] == 'register' && stripos($domain['domain'], '.gr') !== false) {

            $hasDomain = true;

            break;

        }

    }



    // Check for hosting product

    foreach ($vars['products'] as $product) {

        if ($product['pid']) { 

            $hasHosting = true;

            break;

        }

    }



    // Apply the offer code if both domain and hosting are available
    
    if ($hasDomain && $hasHosting) {

        // Obtaining the discount amount and whether it is taxed according to the offer code settings

        $promoCode = Capsule::table('tblpromotions')

            ->where('code', 'dom10') // enter the name of the promo code

            ->first();

        if ($promoCode) {

            $discountAmount = $promoCode->value;

            if ($hasHosting && $hasDomain) {

                $cart_adjustments = [

                    "description" => "Έκπτωση: " . $discountAmount . "€ στο domain",

                    "amount" => -$discountAmount,

                    "taxed" => false,

                ];

            }     

            if (session_status() == PHP_SESSION_NONE) {

                session_start();

            }

            // Display message in cart

            if ($_SERVER['REQUEST_URI'] == '/support/cart.php?a=view') {

                $message = "Εφαρμόστηκε προσφορά: " . " (Έκπτωση: 10€ στο domain)";

                echo "<script>

                    function showPromoMessage() {

                        var targetElement = document.querySelector('.total-due-today.total-due-today-padded');

                        if (targetElement && !document.querySelector('.alert.alert-info')) {

                            var messageDiv = document.createElement('div');

                            messageDiv.className = 'alert alert-info';

                            messageDiv.innerHTML = '" . $message . "';

                            targetElement.parentNode.insertBefore(messageDiv, targetElement);

                        }

                    }

                    if (document.readyState === 'complete') {

                        showPromoMessage();

                    } else {

                        document.addEventListener('DOMContentLoaded', showPromoMessage);

                    }

                    </script>";

                $_SESSION['promo_message_shown'] = true;

            } else {

                unset($_SESSION['promo_message_shown']);

            }

        } else {

            logActivity("Σφάλμα: Ο κωδικός προσφοράς δεν βρέθηκε.");

        }

    }

    return $cart_adjustments;

});

?>
