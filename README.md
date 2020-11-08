This repository is a needed part for getting the Google Tag Manager tracking conversions correctly in 
PrestaShop. 

# module configuration

## install tag manager

* Set up a GTM (Google Tag Manager) container, etc., and copy out the GTM snippet
* open header.tpl
IMMEDIATELY inside the open of the head tag and after the open of the body tag, add...
 {literal}
[your GTM snippet]
{/literal}

## add page view transaction tracking to your shop
Adding page view tracking is super simple. Adding conversion tracking requires a data layer, which I added with this module.

IMPORTANT: For this code to fire, your payment method modules need to be calling PrestaShop after payment. E.g. here are the changes I needed to make to get this to happen with PayPal. As far as I can tell, this "problem" exists for both my module, and for the PrestaShop Google Analytics module.

Install the PrestaShop module "Data Layer Module" (instructions for installing a PrestaShop module can be found in the PrestaShop Documentation). 
It's a VERY basic module that any PHP coder can understand and expand on.

## Inside GTM 

In  GTM, add a new Google Analytics transaction tag... named something like "PrestaShop Conversion"
Add a firing rule (a.k.a. a Trigger) named e.g. "Order Confirmation" with values {{event}} equals prestashop_order_confirmation (prestashop_order_confirmation is an event that I trigger in the Data Layer Module) for the conversion tag.
Preview and debug your conversion tracking.
Publish the GTM container

# about gtm datalayer
```
window.dataLayer = window.dataLayer || [];
window.dataLayer.push({
  'someVar' : 'someVal'
});
```

The first line basically checks if a global variable called dataLayer has already been declared. If it has, it’s left alone and execution proceeds to the push() block. If, however, dataLayer has NOT been defined, the first line then assigns a new, empty Array to it. This ensures that the following push() will always work.
So here’s the recap:
When working on the page template, always check whether or not dataLayer has been defined, and initialize it as a new Array if necessary
**Always, ALWAYS, use push() when interacting with dataLayer** otherwise you overwrite the values sent.