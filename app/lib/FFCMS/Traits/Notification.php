<?php

namespace FFCMS\Traits;

/**
 * Add user notifications messages, typically for user feedback from web pages.
 * The class constructor must set a member $oNotification which contains
 * two methods:
 *     - add which adds notification of $type (e.g warning or into)
 *     - addMultiple which adds multiple messages in $type => $messages[] format
 */
trait Notification
{

    /**
     * @var \FFMVC\Helpers\Notifications user notifications object
     */
    protected $oNotification;


    /**
     * Notify user
     *
     * @param mixed $data multiple messages by 'type' => [messages] OR message string
     * @param string $type type of messages (success, error, warning, info) OR null if multiple $data
     * @return boolean success
     */
    public function notify($data, $type = null)
    {
        if (is_array($data)) {
            return $this->oNotification->addMultiple($data);
        } else {
            return $this->oNotification->add($data, $type);
        }
    }
}
