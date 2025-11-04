<?php



/**

 * Hook για την αυτόματη εφαρμογή κωδικού προσφοράς σε παραγγελίες με domain .gr και πακέτο φιλοξενίας.

 *

 * Αυτός ο hook ενεργοποιείται κατά τη διάρκεια της διαδικασίας ολοκλήρωσης αγοράς.

 * Ελέγχει αν η παραγγελία περιλαμβάνει domain .gr και πακέτο φιλοξενίας.

 * Αν ναι, εφαρμόζει αυτόματα τον κωδικό προσφοράς στο καλάθι και εμφανίζει ένα μήνυμα επιβεβαίωσης.

 *

 * @package   SimTech Web Solutions - WHMCS Hook

 * @version   1.0.1

 * @author    SimTech Web Solutions <info@simtech.gr>

 * @link      https://www.simtech.gr

 * @since     1.0.0

 */



if (!defined("WHMCS")) {

    die("This file cannot be accessed directly");

}



use WHMCS\Database\Capsule;



add_hook('CartTotalAdjustment', 1, function($vars) {

    $cart_adjustments = array();



    $hasDomain = false;

    $hasHosting = false;



    // Έλεγχος για καταχώρηση domain

    foreach ($vars['domains'] as $domain) {

        if ($domain['type'] == 'register' && stripos($domain['domain'], '.gr') !== false) {

            $hasDomain = true;

            break;

        }

    }



    // Έλεγχος για προϊόν φιλοξενίας

    foreach ($vars['products'] as $product) {

        if ($product['pid']) { 

            $hasHosting = true;

            break;

        }

    }



    // Εφαρμογή του κωδικού προσφοράς αν υπάρχουν και domain και hosting

    if ($hasDomain && $hasHosting) {

        // Λήψη του ποσού έκπτωσης και αν φορολογείται από τις ρυθμίσεις του κωδικού προσφοράς

        $promoCode = Capsule::table('tblpromotions')

            ->where('code', 'dom10') 

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



            // Εμφάνιση μηνύματος στο καλάθι

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