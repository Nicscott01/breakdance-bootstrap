<?php

/**
 * @var array $propertiesData
 * @var boolean $renderOnlyIndividualPosts this one is used for "load more" ajax and comes from pagination.php
 */

use function \Breakdance\Util\WP\isAnyArchive;


//require_once __DIR__ . "/post-loop-builder.php";

//$renderOnlyIndividualPosts = $renderOnlyIndividualPosts ?? false;
/*
showWarningInBuilderForImproperUseOfPaginationAndCustomQueriesOnArchives(
    $propertiesData['content']['query']['query'] ?? false,
    $propertiesData['content']['pagination']['pagination'] ?? false,
    isAnyArchive()
);
*/

$actionData = ['propertiesData' => $propertiesData];

global $post;
$initialGlobalPost = $post;

//var_dump( $propertiesData['content']['wp_query']['query'] );

//$query = \Breakdance\WpQueryControl\getWpQueryArgumentsFromWpQueryControlProperties($propertiesData['content']['wp_query']['query']);


$loop = getWpQuery($propertiesData);

//var_dump( $loop );

$layout = (string) ($propertiesData['design']['list']['layout'] ?? '');


do_action("breakdance_posts_loop_before_loop", $actionData);

if ( $loop->have_posts() ) {

$loopIndex = 0;

echo '<div class="posts bs-accordion">';

    while( $loop->have_posts() ) {
        $loopIndex++;

        $block = getBlockForLoopIndex($propertiesData, $loopIndex);

        if ($block['type'] !== 'static') {
            $loop->the_post();
        }

        $itemClasses = get_item_classes($layout);

        $postTag = $propertiesData['content']['repeated_block']['tag'] ?? 'article';
        //$attrs = getFilterAttributesForPost($filterbar, $itemClasses);

        $blockId = $block['id'];


        if ($blockId) {
            $postId = get_the_ID();
            echo \Breakdance\Render\render($blockId, $postId);
        } else {
            if ($_REQUEST['triggeringDocument'] ?? true) {
                echo '<div class="breakdance-empty-ssr-message">Choose a Global Block from the dropdown.</div>';
            } else {
                echo "<!-- Breakdance error: $blockId not found -->";
            }
        }


    }

    echo '</div>';

}


//output_before_the_loop($renderOnlyIndividualPosts, $filterbar, $layout);

//do_the_loop($loop, $layout, $filterbar, $propertiesData, $actionData);

//output_after_the_loop($renderOnlyIndividualPosts, $filterbar, $layout, $propertiesData);

do_action("breakdance_posts_loop_after_loop", $actionData);
/*
\Breakdance\EssentialElements\Lib\PostsPagination\getPostsPaginationFromProperties(
    $propertiesData,
    $loop->max_num_pages,
    $layout,
    \Breakdance\Util\getDirectoryPathRelativeToPluginFolder(__FILE__)
);

do_action("breakdance_posts_loop_after_pagination", $actionData);
*/

wp_reset_postdata();

// If these IDs don't match after resetting the postdata,
// this is a nested post loop, so we need to set the
// post data back to the original value
$currentPostId = $post instanceof \WP_Post ? $post->ID : $post;
$initialPostId = $initialGlobalPost instanceof \WP_Post ? $initialGlobalPost->ID : $initialGlobalPost;
if ($currentPostId && $currentPostId !== $initialPostId) {
    $GLOBALS['post'] = $initialGlobalPost;
}
