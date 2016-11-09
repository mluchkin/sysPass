<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2016, Rubén Domínguez nuxsmin@$syspass.org
 *
 * This file is part of sysPass.
 *
 * sysPass is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * sysPass is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Forms;

use SP\Core\ActionsInterface;
use SP\Core\Exceptions\ValidationException;
use SP\Core\Session;
use SP\DataModel\AccountData;
use SP\DataModel\AccountExtData;
use SP\Http\Request;

/**
 * Class AccountForm
 *
 * @package SP\Account
 */
class AccountForm extends FormBase implements FormInterface
{
    /**
     * @var AccountData
     */
    protected $AccountData;

    /**
     * Validar el formulario
     *
     * @param $action
     * @return bool
     * @throws \SP\Core\Exceptions\ValidationException
     */
    public function validate($action)
    {
        switch ($action) {
            case ActionsInterface::ACTION_ACC_EDIT_PASS:
                $this->checkPass();
                break;
            case ActionsInterface::ACTION_ACC_EDIT:
                $this->checkCommon();
                break;
            case ActionsInterface::ACTION_ACC_NEW:
                $this->checkCommon();
                $this->checkPass();
                break;
        }

        return true;
    }

    /**
     * @throws ValidationException
     */
    protected function checkPass()
    {
        if (!$this->AccountData->getAccountPass()) {
            throw new ValidationException(_('Es necesaria una clave'));
        } elseif (Request::analyzeEncrypted('passR') !== $this->AccountData->getAccountPass()) {
            throw new ValidationException(_('Las claves no coinciden'));
        }
    }

    /**
     * @throws ValidationException
     */
    protected function checkCommon()
    {
        if (!$this->AccountData->getAccountName()) {
            throw new ValidationException(_('Es necesario un nombre de cuenta'));
        } elseif (!$this->AccountData->getAccountCustomerId()) {
            throw new ValidationException(_('Es necesario un nombre de cliente'));
        } elseif (!$this->AccountData->getAccountLogin()) {
            throw new ValidationException(_('Es necesario un usuario'));
        } elseif (!$this->AccountData->getAccountCategoryId()) {
            throw new ValidationException(_('Es necesario una categoría'));
        }
    }

    /**
     * @return mixed
     */
    public function getItemData()
    {
        return $this->AccountData;
    }

    /**
     * Analizar los datos de la petición HTTP
     *
     * @return void
     */
    protected function analyzeRequestData()
    {
        $this->AccountData = new AccountExtData();
        $this->AccountData->setAccountId($this->itemId);
        $this->AccountData->setAccountName(Request::analyze('name'));
        $this->AccountData->setAccountCustomerId(Request::analyze('customerId', 0));
        $this->AccountData->setAccountCategoryId(Request::analyze('categoryId', 0));
        $this->AccountData->setAccountLogin(Request::analyze('login'));
        $this->AccountData->setAccountUrl(Request::analyze('url'));
        $this->AccountData->setAccountNotes(Request::analyze('notes'));
        $this->AccountData->setAccountUserEditId(Session::getUserId());
        $this->AccountData->setAccountOtherUserEdit(Request::analyze('userEditEnabled', 0, false, 1));
        $this->AccountData->setAccountOtherGroupEdit(Request::analyze('groupEditEnabled', 0, false, 1));
        $this->AccountData->setAccountPass(Request::analyzeEncrypted('pass'));
        $this->AccountData->setAccountIsPrivate(Request::analyze('privateEnabled', 0, false, 1));
        $this->AccountData->setAccountPassDateChange(Request::analyze('passworddatechange_unix', 0));

        // Arrays
        $accountOtherGroups = Request::analyze('otherGroups', 0);
        $accountOtherUsers = Request::analyze('otherUsers', 0);
        $accountTags = Request::analyze('tags');

        if (is_array($accountOtherUsers)) {
            $this->AccountData->setUsersId($accountOtherUsers);
        }

        if (is_array($accountOtherGroups)) {
            $this->AccountData->setUserGroupsId($accountOtherGroups);
        }

        if (is_array($accountTags)) {
            $this->AccountData->setTags($accountTags);
        }

        $accountMainGroupId = Request::analyze('mainGroupId', 0);

        // Cambiar el grupo principal si el usuario es Admin
        if ($accountMainGroupId !== 0
            && (Session::getUserIsAdminApp() || Session::getUserIsAdminAcc())
        ) {
            $this->AccountData->setAccountUserGroupId($accountMainGroupId);
        }
    }
}