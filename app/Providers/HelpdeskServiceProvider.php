<?php

namespace IXP\Providers;

/*
 * Copyright (C) 2009 - 2021 Internet Neutral Exchange Association Company Limited By Guarantee.
 * All Rights Reserved.
 *
 * This file is part of IXP Manager.
 *
 * IXP Manager is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation, version v2.0 of the License.
 *
 * IXP Manager is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License v2.0
 * along with IXP Manager.  If not, see:
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */
use Illuminate\Support\ServiceProvider;

use IXP\Contracts\Helpdesk;

use IXP\Console\Commands\Helpdesk\UpdateOrganisations;

use IXP\Services\Helpdesk\{
    ConfigurationException,
    Zendesk
};

/**
 * Helpdesk Service Provider
 *
 * @author     Barry O'Donovan <barry@islandbridgenetowrks.ie>
 * @author     Yann Robin <yann@islandbridgenetowrks.ie>
 * @category   IXP
 * @package    IXP\Providers
 * @copyright  Copyright (C) 2009 - 2020 Internet Neutral Exchange Association Company Limited By Guarantee
 * @license    http://www.gnu.org/licenses/gpl-2.0.html GNU GPL V2.0
 */
class HelpdeskServiceProvider extends ServiceProvider
{

    protected $defer = true;

    protected $commands = [
        UpdateOrganisations::class
    ];

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(): void {}

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton( Helpdesk::class, function( $app ) {
            $backend = $app[ 'config' ][ 'helpdesk' ][ 'backend' ];

            switch( $backend ) {
                case 'none':
                    return false;
                    break;
                case 'zendesk':
                    return new Zendesk( $app[ 'config' ][ 'helpdesk' ][ 'backends' ][ $backend ] );
                    break;
                default:
                    throw new ConfigurationException( 'Invalid, no or unimplemented helpdesk backend requested' );
            }
        });

        $this->commands( $this->commands );
    }


    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return [ Helpdesk::class ];
    }
}