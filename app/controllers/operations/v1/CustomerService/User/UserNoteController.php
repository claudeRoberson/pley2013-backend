<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace operations\v1\CustomerService\User;

use \Pley\Db\AbstractDatabaseManager as DatabaseManager;
use Pley\Entity\User\UserNote;
use Pley\Repository\User\UserNoteRepository;

/**
 * The <kbd>UserNoteController</kbd>
 *
 * @author Sebastian Maldonado(seba@pley.com)
 * @version 1.0
 */
class UserNoteController extends \operations\v1\BaseAuthController
{
    /** @var \Pley\Db\AbstractDatabaseManager */
    protected $_dbManager;
    /** @var \Pley\Dao\User\UserNoteDao */
    protected $_userNoteDao;

    public function __construct(
        DatabaseManager $dbManager,
        \Pley\Repository\User\UserNoteRepository $noteRepository
    )
    {
        parent::__construct();

        $this->_dbManager = $dbManager;
        $this->_noteRepository = $noteRepository;
    }

    // POST /cs/user/{userId}/createNote
    public function create($userId)
    {
        \RequestHelper::checkPostRequest();
        \RequestHelper::checkJsonRequest();
        $json = \Input::json()->all();

        $userNoteEntity = new UserNote();
        $userNoteEntity->setUserId($userId);
        $userNoteEntity->setBody($json['note']['body']);

        $userNote = $this->_noteRepository->save($userNoteEntity);

        if (!$userNote || empty($userNote)) {
            return \Response::json([
                'success' => false
            ]);
        }

        return \Response::json([
            'success' => true,
            'note' => $userNote->toJson()
        ]);
    }

    // GET /cs/user/{userId}/getAllNotes
    public function getAll($userId)
    {
        \RequestHelper::checkGetRequest();

        if (empty($userId)) {
            return \Response::json([
                'success' => false
            ]);
        }

        $notes = $this->_noteRepository->findByUserId($userId);
        $noteArray = [];

        if (!empty($notes)) {
            foreach ($notes as $key => $note) {
                $noteArray[$key] = $note->toJson();
            }
        }

        return \Response::json([
            'success' => true,
            'notes' => $noteArray,
        ]);

    }

    // DELETE /cs/user/note-delete/{id}
    public function delete($id)
    {
        \RequestHelper::checkDeleteRequest();
        $success = $this->_noteRepository->delete($id);
        return \Response::json(['success' => $success]);
    }

}
