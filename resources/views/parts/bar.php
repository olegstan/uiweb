<?php
/**
 * @var Framework\View\View $this
 */
?>
<section id="bar" style="left:<?=\Framework\Auth\Session::get('bar') ? 0 : '-300px' ?>">
    <div id="bar-button" onclick="bar.toggle();">
        <img src="/assets/img/bar.png"/>
    </div>
    <div id="bar-header">
        <ul>
            <li>
                <p>Категории</p>
            </li>
        </ul>
    </div>

    <div id="bar-body">
        <ul class="nav nav-list">
            <?php foreach ($categories as $category) { ?>
                <?php
                /**
                 * @var App\Models\Product\Category $category
                 */
                ?>
                <li><i class="fa fa-plus"></i> <a href="<?=$this->getUrl()->route('catalog_category', ['category_url' => $category->url])?>"><?=$category->name?></a></li>
            <?php } ?>
        </ul>
    </div>
</section>