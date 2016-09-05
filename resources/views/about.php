<?php
/**
 * @var Framework\View\View $this
 */
$this->setLayoutPath('layouts/main.php');
?>
<?php $this->startBuffer('content'); ?>
<div class="container">
    <h2 class="center">Контакты</h2>
    <table class="table">
        <tbody>
            <tr>
                <td>Юр. адрес:</td>
                <td>125413, г. Москва, ул.Флотская, д. 74, комн.14</td>
            </tr>
            <tr>
                <td>Факт. адрес:</td>
                <td>105082, г. Москва, ул. Фридриха Энгельса, д. 56, стр. 1</td>
            </tr>
            <tr>
                <td>Телефон:</td>
                <td>+7 964 789 0125</td>
            </tr>
            <tr>
                <td>E-mail:</td>
                <td><a href="mailto:info@m-view.ru">info@m-view.ru</a></td>
            </tr>
            <tr>
                <td>Название:</td>
                <td>ООО «Модерн Вью»</td>
            </tr>
            <tr>
                <td>ИНН:</td>
                <td>7743883694</td>
            </tr>
            <tr>
                <td>КПП:</td>
                <td>774301001</td>
            </tr>
            <tr>
                <td>ОГРН:</td>
                <td>1137746268868</td>
            </tr>
            <tr>
                <td>Банк:</td>
                <td>ОАО "СМП Банк" г. Москва</td>
            </tr>
            <tr>
                <td>БИК:</td>
                <td>044583503</td>
            </tr>
            <tr>
                <td>р/с:</td>
                <td>40702810500460000694</td>
            </tr>
            <tr>
                <td>к/с:</td>
                <td>30101810300000000503</td>
            </tr>
        </tbody>
    </table>
</div>
<?php $this->endBuffer(); ?>