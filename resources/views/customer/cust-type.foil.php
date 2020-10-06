<?php
/** @var Foil\Template\Template $t */
?>

<?php
    /** @var \IXP\Models\Customer $c */
    $c = $t->cust;
    if( $c instanceof \Entities\Customer ) {
        $c = \IXP\Models\Customer::find( $c->getId() );
    }
?>

<?php if( $c->typeAssociate() ): ?>
    <span class="badge badge-warning tw-p-1">ASSOCIATE MEMBER</span>
<?php elseif( $c->typeProBono() ): ?>
    <span class="badge badge-info">PROBONO MEMBER</span>
<?php elseif( $c->typeInternal() ): ?>
    <span class="badge  badge-primary">INTERNAL INFRASTRUCTURE</span>
<?php elseif( $c->typeFull() ): ?>
    <span class="badge  badge-success">FULL MEMBER</span>
<?php else: ?>
    <span class="badge">UNKNOWN MEMBER TYPE</span>
<?php endif; ?>

<?php if( $c->hasLeft() ): ?>
    <span class="badge  badge-danger">ACCOUNT CLOSED</span>
<?php endif; ?>
<?php if( $t->resellerMode() ): ?>
    <?php if( $c->isReseller ): ?>
        <span class="badge  badge-secondary">RESELLER</span>
    <?php elseif( $c->reseller ): ?>
        <span class="badge  badge-secondary">RESOLD CUSTOMER</span>
    <?php endif; ?>
<?php endif; ?>

<?php if( !$c->typeAssociate()  &&  !$c->isNormal() ): ?>
    <?php if( $c->isNotConnected() ): ?>
        <span class="badge  badge-warning">NOT CONNECTED</span>
    <?php elseif( $c->isSuspended() ): ?>
        <span class="badge  badge-danger">SUSPENDED</span>
    <?php else: ?>
        <span class="badge  badge-primary">UNKNOWN CUSTOMER STATUS</span>
    <?php endif; ?>
<?php endif; ?>