<?php

namespace BricBreakdance\Forms;

/**
* @param array|null $fieldsRepeater
* @param FormData $form
* @param string $fieldControlSlug
* @return array<array-key, mixed|string>
*/
function getMappedFieldValuesFromFormData($fieldsRepeater, $form, $fieldControlSlug, $formFieldSlug = 'formField' )
{
   if (!$fieldsRepeater) return [];

   $fields = [];
   foreach ($form as $field) {
       $fields[$field['advanced']['id']] = $field;
   }

   return array_reduce($fieldsRepeater,
       /**
        * @param array $carry
        * @param array $fieldValue
        */
       function($carry, $fieldValue) use ($fields, $fieldControlSlug, $formFieldSlug ) {
       /** @var string $fieldId */
       $fieldId = $fieldValue[$formFieldSlug] ?? null;
       /** @var string $fieldSlug */
       $fieldSlug = $fieldValue[$fieldControlSlug] ?? null;

       if (array_key_exists($fieldId, $fields)) {
           /** @var DropdownData $field */
           $field = $fields[$fieldId];
           /** @var string $valueInField */
           $valueInField = $field['value'];

           $carry[$fieldSlug] = $valueInField;
       }

       return $carry;
   }, []);
}

