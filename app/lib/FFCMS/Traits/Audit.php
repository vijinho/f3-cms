<?php

namespace FFCMS\Traits;

/**
 * The class constructor should initialise a member $oAudit which contains
 * the method 'write' to write data about changes, which should be an array in
 * the following format:
 *
 *      - uuid: leave empty to auto-generate a uuid for the entry
 *      - users_uuid: (optional) uuid of the user affeced
 *      - actor: (optional) the audit record creator
 *      - event: the type of event audited
 *      - description: (optional) long description of the audit event taking place
 *      - old: (optional) old value(s) before the change
 *      - new: (optional) new value(s) after the change
 *      - debug: (optional) extra debugging information
 */
trait Audit
{
    /**
     * @var \FFCMS\Mappers\Audit audit logging objects
     */
    protected $oAudit;

    /**
     * Write to audit log
     *
     * @param array $data
     *
     * @return bool success
     */
    public function audit($data)
    {
        if (empty($this->oAudit)) {
            return false;
        }
        return $this->oAudit->write($data);
    }
}
