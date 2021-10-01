<?php
/**
 * 2007-2021 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2021 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

class Cart extends CartCore
{
    /**
     * Ajoute les frais de port au transporteur selon les règles définies de "Additional shipping cost by postcode"
     *
     * @param int $id_carrier Carrier ID (default : current carrier)
     * @param bool $use_tax
     * @param Country|null $default_country
     * @param array|null $product_list list of product concerned by the shipping.
     *                                 If null, all the product of the cart are used to calculate the shipping cost
     * @param int|null $id_zone Zone ID
     *
     * @return float|bool Shipping total, false if not possible to ship with the given carrier
     */
    public function getPackageShippingCost($id_carrier = null, $use_tax = true, Country $default_country = null, $product_list = null, $id_zone = null)
    {
        $shippingCost = parent::getPackageShippingCost($id_carrier, $use_tax, $default_country, $product_list, $id_zone);
        if (Module::isEnabled('additionalshippingcostpostcode')) {
            if ($this->id_address_delivery != 0) {
                if (null === $id_carrier && !empty($this->id_carrier)) {
                    $id_carrier = (int)$this->id_carrier;
                }
                if ($id_carrier) {
                    $carrier = self::$_carriers[$id_carrier];
                    $rules = json_decode(Configuration::get('ADDITIONALSHIPPINGCOSTPOSTCODE_RULES'));
                    $skipForFreeShipping = (bool)Configuration::get('ADDITIONALSHIPPINGCOSTPOSTCODE_SKIPFORFREESHIPPING');
                    $address = new Address($this->id_address_delivery);
                    $additionalShippingCost = 0;
                    if (is_array($rules)) {
                        foreach ($rules as $rule) {
                            if (fnmatch($rule->postcode, $address->postcode)) {
                                $currency = Currency::getCurrency((int)$this->id_currency);
                                $additionalShippingCost = Tools::convertPrice((float)$rule->additionalShippingCost, $currency);
                            }
                        }
                    }
                    if (!$carrier->is_free || !$skipForFreeShipping) {
                        $shippingCost += $additionalShippingCost;
                    }
                }
            }
        }
        return $shippingCost;
    }
}
