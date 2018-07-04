# [dolibarr_purchases_module](https://github.com/caos30/dolibarr_purchases_module)

## Description

This Purchases module is a Dolibarr module and it has been developed as an assistant that allows us to quickly and easily make the purchase of X products:

- Being able to compare prices from several suppliers, even in different currencies.
- Being able to send by mail with a single click invitations to our selected suppliers to visit a link with which they access a simple multi-language form in which they can capture the prices for us, decline or confirm the sale of any of the requested products, and add some extra comment about delivery and/or payment conditions.
- Being able to modify / add / remove prices from any supplier to any product from that same price table, with just two clicks.
- Being able to create the corresponding orders also with two clicks, filling automatically with all the information of each supplier and the products. 

In short, an interface easier and more agile to use, that allows you to do the same thing that you can already do with the native Dolibarr interface but in a much more comfortable way. Well, in Dolibarr it would be very difficult to compare the prices of each product for each supplier and then assemble the different orders. And obviously there is no way to suppliers capture the prices you need.

Note: given that Dolibarr has an incomplete treatment and in some points incongrant handling different currencies (EUR, USD, MXN, MAD, etc ...) for the prices of the products, there has been no choice but to implement a small patching of Dolibarr . At the end of this guide we explain how to do it (it is a simple copy and paste of no more than 20 lines of code in a single PHP file on Dolibarr core). But it is necessary that before you continue reading you know that if you want this Purchases module to work well in a multi-currency environment you will have to do that patching of Dolibarr. 

## Interface language translations

Until now: English / French / Catalan / Castillian (spanish) / Polish

## Slide presentation

[https://slides.com/caos30/dolibarr-purchases-en](https://slides.com/caos30/dolibarr-purchases-en)

## Initial author and history

Caos30 was the initial developer of this module, made for an specific customer on 2017. One year afterwards, caos30 decided in July 2018 to liberate the code of the module to make easy the contribution of other users, testers and developers. The final target is to be added to the core of the Dolibarr CMS, when the module be enough mature. 

## Installation

The usual to any other module of Dolibarr. But also take in account that if you will use more than one currency on your Dolibarr then you need to apply a minor patch on Dolibarr core (at least on versions 5.x and 6.X).

Complete information about all this on the [official wiki of the module](https://wiki.dolibarr.org/index.php/Module_Purchases).

## License

LICENSE: GPL v3

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 3
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.

## Links

- https://www.dolistore.com/es/modulos/865-Purchases.html
- http://slides.com/caos30/dolibarr-purchases-en
- http://slides.com/caos30/dolibarr-purchases
- User guide: https://imasdeweb.com/index.php?pag=m_blog&gad=detalle_entrada&entry=36
- Manual de usuario: https://imasdeweb.com/index.php?pag=m_blog&gad=detalle_entrada&entry=35

## Team

As developers & translators: 

 - DEV: Sergi Rodrigues (from 2017)
 - English en_GB: Aljawaid (from 2017)
 - Polish pl_PL: Marcin Szczepanczyk (from 2017)

## Versions Log

== 1.1 [2017-11-20]

 + First version

== 1.2 [2017-11-29]

 + added polish translation
 + fixed grammatical issues on english translation
 + made compatible the module with /custom install
 + fixed an error when the module Projects is not enabled on Dolibarr

== 1.3 [2017-11-30]

 + supplier quotation form: added name of country languages on the tooltips of the flag buttons
 + purchase edit page: removed the numbers from the status circles on the list of products. They were confusing.

== 1.4 [2017-11-30]

 + added french translation

== 1.5

 + purchase edit page: easter egg to render the raw data of the purchase on database when double clicking a hidden link
 + purchase edit page: render a message button when the order of a product was removed or replaced.

== 1.6 [2018-02-28]

 + minor fix: removed PHP notices when $page is not set on purchases list

== 1.7 [2018-02-28]

 + minor fix: fixed htmlentities on some alert() javascript messages

== 1.8 [2018-03-03]

 + provider web request form, added 2 fields: town and zip code


## To do

 - quotation form: when supplier keypress a comma on Unit price box, replace it with a dot (if possible in the same position). Also include on instructions section the comment "Use dot for decimal numbers".
 - dialog box for sending quotation requests by email: be able to choose email address of the supplier, because we can need to send the email to only one of the departments of that company
 - module settings: textarea for customize "footer email" sent to customers for quotation. The optimal solution would be to create a new Dolibarr template for this email
 - product thumbnail on quotation form... it's a great idea.
 - Module settings: be able to specify the sender email address to avoid be spammed.
 - compatible with product bar codes

 - product codes/references of the supplier:

 -- it's necessary to let supplier to input/edit these codes/refs on the quotation form
 -- possibly it is convenient to let the buyer change it also from the list of products on a purchase edit page
 -- Explanation from Marcin (7DIC2017):
Product reference to be inputted manually for both the buyer (if he knows the code) and the seller if the buyer doesn't know the code or the code stated by the buyer is wrong and needs to be modified. At the moment it is a random number “730_1512648539” but in the business most of the companies work on the product codes and not on the description (especially if you purchasing for a different country) but please note that the same product can have a different or the same code across the suppliers. So there would need to be a field next to each product (in the table of products with the prices of the suppliers) that imports the 3rd party product reference (if exists) but if it doesn’t then it stays blunk to be inputed manually if necessary. And this field would need to appear on the form for supplier so he can see what is the code or add the code if the field is blank. That would be a great thing and will help me a lot and I’m sure that it will help others as well.

 - supplier quotation form: column for SUPPLIER REFERENCE of the product
 - supplier quotation form: button for print or download PDF


