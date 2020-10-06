#
# This file contains Nagios configuration for monitoring Birdseye daemons
#
# WARNING: this file is automatically generated using the
#   api/v4/nagios/switches/{infraid} API call to IXP Manager.
#
# Any local changes made to this script will be lost.
#
# See: http://docs.ixpmanager.org/features/nagios/
#
# You should not need to edit these files - instead use your own custom skins. If
# you can't effect the changes you need with skinning, consider posting to the mailing
# list to see if it can be achieved / incorporated.
#
<?php if( $t->vlanid ): ?>
# Limited to VLAN ID: <?= $t->vlanid ?>
<?php endif; ?>
#
# Generated: <?= date( 'Y-m-d H:i:s' ) . "\n" ?>
#
#
# The following objects are used by inheritance here and need to be defined by your own configuration:
#
# 1. Host definition:    <?= $t->host_definition ?>;
# 2. Service definition: <?= $t->service_definition ?>.
#
# You would create these yourself by creating a configuration file containing something like:
#
# define host {
#     name                    <?= $t->host_definition . "\n" ?>
#     check_command           check-host-alive
#     check_period            24x7
#     max_check_attempts      10
#     notification_interval   120
#     notification_period     24x7
#     notification_options    d,u,r
#     contact_groups          admins
#     register                0
# }
#
#
# define service {
#     name                    <?= $t->service_definition . "\n" ?>
#     service_description     Bird BGP Service
#     check_command           check_birdseye_daemon!$_HOSTAPIURL$
#     check_period            24x7
#     max_check_attempts      10
#     check_interval          5
#     retry_check_interval    1
#     contact_groups          admins
#     notification_interval   120
#     notification_period     24x7
#     notification_options    w,u,c,r
#     register                0
# }
#

<?php
    $hosts = [];
    $hg_name  = 'bird-daemons'          . ( $t->vlanid ? ('-vlanid-' . $t->vlanid) : '' );

    foreach( $t->routers as $n => $d ):

    if( !$d->api() ) {
        continue;
    }

    $hosts[] = "bird-{$d->getHandle}";
?>

define host     {
        use                     <?= $t->host_definition . "\n" ?>
        host_name               bird-<?= $d->handle . "\n" ?>
        alias                   <?= $d->name . "\n" ?>
        address                 <?= $d->mgmt_host . "\n" ?>
        _apiurl                 <?= $d->api . "\n" ?>
}

define service     {
    use                     <?= $t->service_definition . "\n" ?>
    host_name               bird-<?= $d->handle . "\n" ?>
}

<?php endforeach; ?>

###############################################################################################
###############################################################################################
###############################################################################################
###############################################################################################
###############################################################################################
###############################################################################################

<?php asort( $hosts ); ?>

define hostgroup {
    hostgroup_name          <?= $hg_name . "\n" ?>
    alias                   Individual Bird Daemons
    members                 <?php echo $t->softwrap( $hosts, 1, ', ', ', \\', 28 ) . "\n" ?>
}


### END ###
