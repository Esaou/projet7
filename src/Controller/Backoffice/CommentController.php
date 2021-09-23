<?php

declare(strict_types=1);

namespace  App\Controller\Backoffice;

use App\Model\Entity\Comment;
use App\Model\Repository\UserRepository;
use App\Service\Authorization;
use App\Service\Http\RedirectResponse;
use App\Service\Http\Request;
use App\Service\Http\Response;
use App\Service\Http\Session\Session;
use App\Service\Paginator;
use App\View\View;
use App\Model\Repository\PostRepository;
use App\Model\Repository\CommentRepository;

final class CommentController
{
    private PostRepository $postRepository;
    private CommentRepository $commentRepository;
    private UserRepository $userRepository;
    private View $view;
    private Request $request;
    private Session $session;
    private Paginator $paginator;
    private RedirectResponse $redirect;
    private Authorization $security;

    public function __construct(
        View $view,
        Request $request,
        Session $session,
        CommentRepository $commentRepository,
        UserRepository $userRepository,
        PostRepository $postRepository,
        Paginator $paginator,
        Authorization $security,
        RedirectResponse $redirectResponse
    ) {

        $this->postRepository = $postRepository;
        $this->commentRepository = $commentRepository;
        $this->userRepository = $userRepository;
        $this->view = $view;
        $this->request = $request;
        $this->session = $session;
        $this->paginator = $paginator;
        $this->security = $security;
        $this->redirect = $redirectResponse;

        if (!$this->security->isLogged() || $this->security->loggedAs('User')) {
            $this->redirect->redirect('forbidden');
        }
    }

    public function commentList():Response
    {

        if (!is_null($this->request->query()->get('delete'))) {
            $id = $this->request->query()->get('id');
            $comment = $this->commentRepository->findOneBy(['id' => $id]);

            if (!is_null($comment)) {
                $this->commentRepository->delete($comment);
                $this->session->addFlashes('danger', 'Commentaire supprimé avec succès !');
            }
        }

        if (!is_null($this->request->query()->get('validate'))) {
            $id = $this->request->query()->get('id');
            $comment = $this->commentRepository->findOneBy(['id' => $id]);

            if (!is_null($comment)) {
                $comment->setIsChecked('Oui');
                $this->commentRepository->update($comment);
                $this->session->addFlashes('success', 'Commentaire validé avec succès !');
            }
        }

        if (!is_null($this->request->query()->get('unvalidate'))) {
            $id = $this->request->query()->get('id');
            /** @var Comment $comment */
            $comment = $this->commentRepository->findOneBy(['id' => $id]);

            $comment->setIsChecked('Non');
            $this->commentRepository->update($comment);

            $this->session->addFlashes('success', 'Commentaire invalidé avec succès !');
        }

        // PAGINATION

        $tableRows = $this->commentRepository->countAllComment();

        $this->paginator->paginate($tableRows, 10, 'comments');

        $comments = $this->commentRepository->findBy(
            [],
            ['createdDate' =>'desc'],
            $this->paginator->getLimit(),
            $this->paginator->getOffset()
        );

        return new Response($this->view->render([
            'template' => 'comments',
            'type' => 'backoffice',
            'data' => [
                'comments' => $comments,
                'paginator' => $this->paginator->getPaginator()
            ],
        ]));
    }
}
