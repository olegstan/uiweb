<?php
/**
 * @var Framework\View\View $this
 */
$this->setLayoutPath('layouts/account.php');
?>
<?php $this->startBuffer('content'); ?>
<div class="container">
        <h2>Личный кабинет</h2>
        <p></p>
        <table class="table">
            <thead>
            <tr>
                <th>№</th>
                <th>Название</th>
            </tr>
            </thead>
            <tbody>
            <tr>
            </tr>
            <tr>
                <td>Итого:</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td data-sum></td>
                <td></td>
            </tr>
            </tbody>
            <tfoot>
            <tr>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
            </tr>
            </tfoot>
        </table>
</div>
<?php $this->endBuffer(); ?>
<?php $this->startBuffer('footer'); ?>
<?php $this->endBuffer(); ?>
