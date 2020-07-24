<?php
    /** @var Foil\Template\Template $t */
    $this->layout( 'layouts/ixpv4' )
?>

<?php $this->section( 'page-header-preamble' ) ?>
    Route Server Filter
    /
    <?= $t->c->name ?> / <?= $t->rsf ? 'Edit' : 'Create' ?>
<?php $this->append() ?>

<?php $this->section( 'page-header-postamble' ) ?>
    <div class="btn-group btn-group-sm" role="group">
        <a class="btn btn-white" href="<?= route ('rs-filter@list', [ "cust" => $t->c->id ] ) ?>" title="list">
            <span class="fa fa-list"></span>
        </a>
        <?php if( $t->rsf ): ?>
            <a class="btn btn-white" href="<?= route('rs-filter@view', [ "rsf" => $t->rsf->id ] ) ?>" title="view route serve filter">
                <i class="fa fa-eye"></i>
            </a>
        <?php endif; ?>
    </div>
<?php $this->append() ?>

<?php $this->section('content') ?>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-body">
                    <?= Former::open()
                        ->method( $t->rsf ? 'PUT' : 'POST' )
                        ->action( $t->rsf ? route( 'rs-filter@update' , [ 'rsf' => $t->rsf->id ] ) : route( 'rs-filter@store' ) )
                        ->customInputWidthClass( 'col-lg-4 col-md-6 col-sm-6' )
                        ->customLabelWidthClass( 'col-sm-4 col-md-4 col-lg-3' )
                        ->actionButtonsCustomClass( "grey-box");
                    ?>

                    <?= Former::select( 'peer_id' )
                        ->label( 'Peer' )
                        ->fromQuery( $t->peers, 'name' )
                        ->placeholder( 'Choose a Peer' )
                        ->addClass( 'chzn-select' );
                    ?>

                    <?= Former::select( 'vlan_id' )
                        ->label( 'Lan' )
                        ->option( 'all' )
                        ->fromQuery( $t->vlans, 'name' )
                        ->placeholder( 'Choose a Lan' )
                        ->addClass( 'chzn-select' );
                    ?>

                    <?= Former::select( 'protocol' )
                        ->label( 'Protocol' )
                        ->fromQuery( [ null => 'All' ] + $t->protocols )
                        ->placeholder( 'Choose the protocol' )
                        ->addClass( 'chzn-select' );
                    ?>

                    <div class="form-group row">
                        <label for="received_prefix" class="control-label col-sm-4 col-md-4 col-lg-3">Received Prefix</label>
                        <div class="col-lg-4 col-md-6 col-sm-6" id="area_received_prefix">
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="advertised_prefix" class="control-label col-sm-4 col-md-4 col-lg-3">Advertise Prefix</label>
                        <div class="col-lg-4 col-md-6 col-sm-6" id="area_advertised_prefix">
                        </div>
                    </div>

                    <?= Former::select( 'action_advertise' )
                        ->label( 'Action Advertise' )
                        ->fromQuery( \IXP\Models\RouteServerFilter::$ADVERTISE_ACTION_TEXT )
                        ->placeholder( 'Choose advertise action' )
                        ->addClass( 'chzn-select' );
                    ?>

                    <?= Former::select( 'action_receive' )
                        ->label( 'Action Receive' )
                        ->fromQuery( \IXP\Models\RouteServerFilter::$RECEIVE_ACTION_TEXT )
                        ->placeholder( 'Choose receive action' )
                        ->addClass( 'chzn-select' );
                    ?>

                    <?= Former::hidden( 'id' )
                        ->value( $t->rsf ? $t->rsf->id : '' )
                    ?>

                    <?= Former::hidden( 'custid' )
                        ->id( 'custid' )
                        ->value( $t->rsf ? $t->rsf->customer->id : $t->c->id )
                    ?>

                    <?= Former::actions(
                        Former::primary_submit( $t->rsf ? 'Save Changes' : 'Create' )->class( "mb-2 mb-sm-0" ),
                        Former::secondary_link( 'Cancel' )->href(  route( 'rs-filter@list', [ "cust" => $t->rsf ? $t->rsf->customer->id : $t->c->id ] ) )->class( "mb-2 mb-sm-0" ),
                        Former::success_button( 'Help' )->id( 'help-btn' )->class( "mb-2 mb-sm-0" )
                    );
                    ?>

                    <?= Former::close() ?>
                </div>
            </div>
        </div>
    </div>
<?php $this->append() ?>

<?php $this->section( 'scripts' ) ?>
    <?= $t->insert( 'rs-filter/js/edit' ); ?>
<?php $this->append() ?>