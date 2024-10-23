<?php

/**
 * 
 * 
 * 
 * 
 */

 $id = $propertiesData['content']['data']['slug'];
 $markup_as_faq = $propertiesData['content']['data']['markup_as_faq'] ?? false;
 $title = $propertiesData['content']['data']['title'];

 //{{ macros.atomV1IconHtml('accordion-icon', content.data.arrow, false, false, design.icon.icon) }}
 //atomV1IconHtml(className, icon, rotate, link, iconDesign)
 //$template = "{{ macros.atomV1IconHtml('accordion-icon', content.data.arrow, false, false, design.icon.icon) }}";
 $template = "{{ macros.atomV1IconHtml( className, icon, rotate, link, icon ) }}";
 

 $icon = Breakdance\Render\Twig::getInstance()->runTwig($template, [
    'className' => 'accordion-icon',
    'icon' => $propertiesData['content']['data']['arrow'],
    'rotate' => false,
    'link' => false,
    'iconDesign' => $propertiesData['design']['icon']['icon'],
 ] );

?>
<div id="accordion-item-%%UNIQUESLUG%%" <?php echo $markup_as_faq ? 'itemscope itemtype="https://schema.org/FAQPage"' : ''; ?>>
  
  <div class="accordion-header" 
      id="accordion-heading-item-%%UNIQUESLUG%%" <?php echo $markup_as_faq ? 'itemscope itemprop="mainEntity" itemtype="https://schema.org/Question"' : ''; ?>>
      
    <button 
      class="accordion-button button-atom collapsed" 
      type="button" 
      data-bs-toggle="collapse" 
      data-bs-target="#accordion-body-item-%%UNIQUESLUG%%" 
      aria-expanded="true" 
      aria-controls="accordion-body-item-%%UNIQUESLUG%%">
      
      <h2 class="heading" 
      <?php echo $markup_as_faq ? 'itemprop="name"' : ''; ?>>
        <?php echo $title; ?>
      </h2>
      
      <?php echo $icon; ?>
    </button>
  </div>
<?php
    //Only print something in the data-bs-parent attribute if we have it set in the parent.
    $parent_el = '';
    $parent_id = \ParentIDTracker()->get_parent_id();

    if ( !empty( $parent_id ) ) {

        $parent_el = '#' . $parent_id;

    }   
?>
  <div 
    id="accordion-body-item-%%UNIQUESLUG%%" 
    class="accordion-collapse collapse" 
    aria-labelledby="accordion-heading-item-%%UNIQUESLUG%%" 
    data-bs-parent="<?php echo $parent_el; ?>">
    <div class="accordion-body" 
    <?php echo $markup_as_faq ? 'itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer"' : ''; ?>>
      <p <?php echo $markup_as_faq ? 'itemprop="text"' : ''; ?>>
        %%CHILDREN%%
      </p>
    </div>
  </div>
</div>
