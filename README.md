# Catalog GRAPHQL plugin for Magento2

This is a Magento2 plugin with the purpose of customizing the output for the select/dropdown Magento2 catalog attributes.

## Functionality

In a clean Magento2 project, the GraphQL endpoint will display the numeric value of the option id for the the single-value / select / dropdown attribute (instead of the textual option). This repository will allow you to:

1. Access attribute values based on the entity ID & store view
2. Access any select/dropdown/filterable attribute in a simple JSON format
   The output of the GraphQL query will be in the format:

`<attribute-code>:{
"code":"<attribute-option-key-for-admin-view>",
"value":"<attribute-option-value>"
}`

## Requirements
This integration has been developed on Magento 2.4.3

## Setup
If you wish to use this simple flow for customizing any Magento2 select attribute, please install the repository as is:

`composer require dannegwork/attribute-of-type-select-graphql-magento2`

`bin/magento module:enable DannegWork_CatalogGraphql`

`bin/magento setup:upgrade`

### Sample GraphQL Request

The provided QL works on a Magento 2.4.3 setup, deployed with sample data:

`{
  products (search: "fusion", pageSize: 5)
  {
    items {
      uid
      name
      sku
      color {
        code
        value
      }
      format {
        code
        value
      }
    }
  }
}`

Color & format are select fields.

## Contact me!
If you have any question, just contact me at contact@danneg.work
