<?php
/**
 * @var $this Framework\View\View
 */
?>
<header id="header">
    <div id="first-menu">
        <div class="container">
            <div id="logo"><a href="<?=$this->getUrl()->route('main')?>">UIweb</a></div>
            <ul>
                <li>
                    <a class="header-link" href="<?=$this->getUrl()->route('main')?>">Главная</a>
                </li>
                <li>
                    <a class="header-link" href="<?=$this->getUrl()->route('catalog')?>">Каталог</a>
                </li>
                <li>
                    <a class="header-link" href="<?=$this->getUrl()->route('about')?>">О компании</a>
                </li>
                <?php if($this->getAuth()->isAuth()){ ?>
                    <li>
                        <a class="header-link" href="<?=$this->getUrl()->route('info')?>">Личный кабинет</a>
                    </li>
                    <li>
                        <a class="header-link" href="<?=$this->getUrl()->route('logout')?>">Выйти</a>
                    </li>
                <?php }else{ ?>
                    <li>
                        <a class="header-link" href="<?=$this->getUrl()->route('login')?>">Войти</a>
                    </li>
                    <li>
                        <a class="header-link" href="<?=$this->getUrl()->route('register')?>">Регистрация</a>
                    </li>
                <?php } ?>
<!--                <li>-->
<!--                    <a class="header-link" href="">asd</a>-->
<!--                </li>-->
<!--                <li>-->
<!--                    <a class="header-link" href="">asd</a>-->
<!--                </li>-->
<!--                <li>-->
<!--                    <a class="header-link" href="">asd</a>-->
<!--                </li>-->
            </ul>
        </div>
    </div>
    <div id="second-menu">
        <div class="container">
            <div class="col-lg-offset-6 col-lg-4">
                <form action="<?=$this->getUrl()->route('catalog_seaech')?>" method="get">
                    <input type="text" name="q" placeholder="Введите поисковый запрос" value="<?=(isset($search) ? $search : '')?>">
                    <button type="submit"><i class="fa fa-search fa-2x"></i></button>
                </form>
            </div>
            <div class="col-lg-2">
                <?=$this->render('parts/cart.php')?>
            </div>
        </div>
    </div>
</header>