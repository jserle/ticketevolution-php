<?php

/**
 * TicketEvolution Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://github.com/ticketevolution/ticketevolution-php/blob/master/LICENSE.txt
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@teamonetickets.com so we can send you a copy immediately.
 *
 * @category    TicketEvolution
 * @package     TicketEvolution\DataLoader
 * @author      J Cobb <j@teamonetickets.com>
 * @author      Jeff Churchill <jeff@teamonetickets.com>
 * @copyright   Copyright (c) 2012 Team One Tickets & Sports Tours, Inc. (http://www.teamonetickets.com)
 * @license     https://github.com/ticketevolution/ticketevolution-php/blob/master/LICENSE.txt     New BSD License
 */


namespace TicketEvolution\DataLoader\Events;
use TicketEvolution\DataLoader\AbstractDataLoader;


/**
 * DataLoader for a specific API endpoint to cache the data into local table(s)
 *
 * @category    TicketEvolution
 * @package     TicketEvolution\DataLoader
 * @copyright   Copyright (c) 2012 Team One Tickets & Sports Tours, Inc. (http://www.teamonetickets.com)
 * @license     https://github.com/ticketevolution/ticketevolution-php/blob/master/LICENSE.txt     New BSD License
 */
class Deleted extends AbstractDataLoader
{
    /**
     * Which endpoint we are hitting. This is used in the `dataLoaderStatus` table
     *
     * @var string
     */
    var $endpoint = 'events';


    /**
     * The type of items to get [active|deleted]
     *
     * @var string
     */
    var $endpointState = 'deleted';


    /**
     * The class of the table
     *
     * @var \Zend_Db_Table
     */
    protected $_tableClass = '\TicketEvolution\Db\Table\Events';


    /**
     * Perform the API call
     *
     * @param array $options Options for the API call
     * @return \TicketEvolution\Webservice\ResultSet
     */
    protected function _doApiCall(array $options)
    {
        try {
            return $this->_webService->listEventsDeleted($options);
        } catch(Exceotion $e) {
            throw new namespace\Exception($e);
        }
    }


    /**
     * Manipulates the $result data into an array to be passed to the table row
     *
     * @param object $result    The current result item
     * @return void
     */
    protected function _formatData($result)
    {
        // Ensure the timezone is not incorrectly adjusted
        $occursAt = preg_replace('/[Z]/i', '', $result->occurs_at);

        $this->_data = array(
            'eventId'           => (int)    $result->id,
            'eventName'         => (string) $result->name,
            'eventDate'         => (string) $occursAt,
            'eventUrl'          => (string) $result->url,
            'updated_at'        => (string) $result->updated_at,
            'eventStatus'       => (int)    0,
        );

        if (!empty($result->created_at)) {
            $this->_data['created_at'] = (string) $result->created_at;
        }

        if (!empty($result->deleted_at)) {
            $this->_data['deleted_at'] = (string) $result->deleted_at;
        }

    }


    /**
     * Allows pre-save logic to be applied.
     * Subclasses may override this method.
     *
     * @param object $result    The current result item. Only passed to enable progress output
     * @return void
     */
    protected function _preSave($result)
    {
    }


    /**
     * Allows post-save logic to be applied.
     * Subclasses may override this method.
     *
     * @param object $result    The current result item
     * @return void
     */
    protected function _postSave($result)
    {
        /**
         * The easiest way to set the tevoPerformers for this event to inactive
         * is to delete() this event and let the operation cascade to the
         * tevoEventPerformers.
         *
         * So, although it is kind of redundant to delete() since we just save()ed
         * as inactive we'll still do it since delete() does not allow us to
         * set properties such as 'deleted_at' at the same time.
         *
         * NOTE: delete() is overridden in TicketEvolution_Db_Table_Abstract to
         * only toggle the status to inactive, but it still cascades doing the same.
         */
        try {
            $this->_tableRow->delete();

            if ($this->_showProgress) {
                echo '<p class="error">'
                   . htmlentities('Successful delete() of ' . $result->id . ': ' . $result->name . ' in `tevoEvents` and the related `tevoEventPerformers`', ENT_QUOTES, 'UTF-8', false)
                   . '</p>' . PHP_EOL;
            }
        } catch (Exception $e) {
            if ($this->_showProgress) {
                echo '<p>'
                   . htmlentities('Error attempting to delete() ' . $result->id . ': ' . $result->name . ' in `tevoEvents` and the related `tevoEventPerformers`', ENT_QUOTES, 'UTF-8', false)
                   . '</p>' . PHP_EOL;
            }

            throw new namespace\Exception($e);
        }
    }


}
