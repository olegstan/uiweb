<?php
/**
 * @var Framework\View\View $this
 */
?>

<section id="breadcrumbs">
    <div class="container">
            <?php
            if(isset($breadcrumbs)){
                foreach ($breadcrumbs as $breacrumb) {
                    echo '<li><a href="' . $breacrumb['link'] . '">' . $breacrumb['text'] . '</a></li>';
                }
            }
            ?>
    </div>
</section>



