<?php

namespace ThULBAdmin\Controller;

use Laminas\Db\Sql\Select;
use VuFind\Controller\AbstractBase;
use VuFind\Log\LoggerAwareTrait;
use Laminas\Http\Response;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Model\ViewModel;

class UserdataController extends AbstractBase {
    use LoggerAwareTrait;

    /* @var \VuFind\Db\Table\PluginManager */
    protected $dbTables;

    protected $dataTables;

    protected $resultPluginManager;

    /**
     * Constructor
     *
     * @param ServiceLocatorInterface $sm Service manager
     */
    public function __construct (ServiceLocatorInterface $sm) {
        parent::__construct($sm);

        $this->accessPermission = 'access.AdminModule';

        $this->dbTables = $sm->get('VuFind\Db\Table\PluginManager');
        $this->resultPluginManager = $sm->get(\VuFind\Search\Results\PluginManager::class);

        $this->dataTables = array (
            array ('name' => 'comments'),
            array ('name' => 'resourcetags'),
            array ('name' => 'search'),
            array ('name' => 'usercard'),
            array (
                'name' => 'userlist',
                'alias' => 'ul',
                'selectForCheck' => (new Select())
                    ->from(['ul' => 'user_list'])
                    ->join(['ur' => 'user_resource'], 'ul.id = ur.list_id', ['entries' => new \Laminas\Db\Sql\Expression('count(ur.resource_id)')])
                    ->columns(['id', 'title', 'description', 'public'])
                    ->group('ur.list_id')
            ),
            array (
                'name' => 'userresource',
                'alias' => 'ur',
                'selectForCheck' => (new Select())
                    ->from(['ur' => 'user_resource'])
                    ->join(['r'  => 'resource'], 'ur.resource_id = r.id', ['record_title' => 'title', 'record_id'])
                    ->join(['ul' => 'user_list'], 'ul.id = ur.list_id', ['list_title' => 'title'])
                    ->columns(['id'])
            )
        );
    }

    public function reassignAction() : ViewModel{
        if(!$this->getAuthManager()->isLoggedIn()) {
            return $this->forceLogin();
        }

        $checkData = [];

        if($this->getRequest()->isPost()) {
            $oldUserNumber = $this->getRequest()->getPost('oldUserNumber');
            $newUserNumber = $this->getRequest()->getPost('newUserNumber');

            if($oldUserNumber && $newUserNumber) {
                /* @var $userTable \VuFind\Db\Table\User */
                $userTable = $this->dbTables->get('User');

                $checkData['oldUser'] = $oldUser = $userTable->getByUsername($oldUserNumber)->toArray();
                $checkData['newUser'] = $userTable->getByUsername($newUserNumber)->toArray();

                foreach($this->dataTables as $table) {
                    $name = $table['name'];
                    if(($select = $table['selectForCheck'] ?? false) && ($alias = $table['alias'] ?? false)) {
                        $checkData[$name] = $this->dbTables->get($name)
                            ->selectWith($select->where("$alias.user_id = {$oldUser['id']}"));
                    }
                    else {
                        $checkData[$name] = $this->dbTables->get($name)->select('user_id = ' . $oldUser['id']);
                    }

                    if($name != 'search') {
                        $checkData[$name] = $checkData[$name]->toArray();
                    }
                    else {
                        $searches = $checkData[$name];
                        $checkData[$name] = array();

                        foreach($searches as $search) {
                            $deminified = $search
                                ->getSearchObject()
                                ->deminify($this->resultPluginManager);

                            $checkData[$name][] = array(
                                'searchClass' => $deminified->getParams()->getSearchClassId(),
                                'queryString' => $deminified->getParams()->getQuery()->getString()
                            );
                        }
                    }
                }
            }
        }

        return new ViewModel([
            'oldUserNumber' => $oldUserNumber ?? '',
            'newUserNumber' => $newUserNumber ?? '',
            'checkData' => $checkData
        ]);
    }

    /**
     * Save message data.
     *
     * @return Response
     */
    public function saveAction() : Response {
        if(!$this->getAuthManager()->isLoggedIn()) {
            return $this->forceLogin();
        }

        if($this->getRequest()->isPost()) {
            $oldUserNumber = $this->getRequest()->getPost('oldUserNumber');
            $newUserNumber = $this->getRequest()->getPost('newUserNumber');

            if($oldUserNumber && $newUserNumber) {
                try {
                    /* @var $userTable \VuFind\Db\Table\User */
                    $userTable = $this->dbTables->get('User');

                    $oldUser = $userTable->getByUsername($oldUserNumber, false);
                    $newUser = $userTable->getByUsername($newUserNumber, false);

                    if (!$newUser->offsetExists('id')) {
                        $newUser = $userTable->createRowForUsername($newUserNumber);
                        $newUser->save();
                    }

                    foreach ($this->dataTables as $table) {
                        $this->dbTables->get($table['name'])->update(
                            ['user_id' => $newUser->offsetGet('id')],
                            ['user_id' => $oldUser->offsetGet('id')]
                        );
                    }

                    $this->flashMessenger()->addMessage('Nutzerdaten wurden neu zugeordnet.', 'success');
                }
                catch (\Exception $e) {
                    $this->flashMessenger()->addMessage('Bei dem umschreiben ist ein Fehler aufgetreten.', 'error');
                }
            }
        }

        return $this->redirect()->toUrl('/ReassignUserdata');
    }

    public function deleteAction () : ViewModel {
        if(!$this->getAuthManager()->isLoggedIn()) {
            return $this->forceLogin();
        }

        $checkData = [];

        if($this->getRequest()->isPost()) {
            $userNumber = $this->getRequest()->getPost('userNumber');
            $checkData = $this->dbTables
                ->get('User')
                ->getByUsername($userNumber, false);

            if(!$checkData) {
                $this->flashMessenger()->addmessage('User not found', 'error');
            }
        }

        return new ViewModel([
            'userNumber' => $userNumber ?? '',
            'checkData' => $checkData ? $checkData->toArray() : null
        ]);
    }

    public function confirmDeleteAction () : Response {

        if($this->getRequest()->isPost()) {
            $userNumber = $this->getRequest()->getPost('userNumber');

            if($this->dbTables->get('User')->getByUsername($userNumber)->delete()) {
                $this->flashMessenger()->addmessage('User deleted', 'success');
            }
            else {
                $this->flashMessenger()->addmessage('Could not delete user', 'error');
            }

        }

        return $this->redirect()->toUrl('/Userdata/delete');
    }
}