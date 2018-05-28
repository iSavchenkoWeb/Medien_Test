<?php
	namespace AppBundle\Acl;

	use FOS\CommentBundle\Acl\RoleCommentAcl as BaseRoleCommentAcl;
	use FOS\CommentBundle\Model\CommentInterface;
	use FOS\CommentBundle\Model\SignedCommentInterface;
	use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

	class RoleCommentAcl extends BaseRoleCommentAcl {

		/**
		 * The current Security Context.
		 *
		 * @var AuthorizationCheckerInterface
		 */
		private $securityContext;
		private $tokenStorage;
		/**
		 * Constructor.
		 *
		 * @param  						   $securityContext
		 * @param string                   $createRole
		 * @param string                   $viewRole
		 * @param string                   $editRole
		 * @param string                   $deleteRole
		 * @param string                   $commentClass
		 */
		public function __construct(AuthorizationCheckerInterface $securityContext, $tokenStorage, $createRole, $viewRole, $editRole, $deleteRole, $commentClass
		) {
		    parent::__construct(
		            $securityContext, $createRole, $viewRole, $editRole, $deleteRole, $commentClass);

		    $this->tokenStorage = $tokenStorage;
		    $this->securityContext = $securityContext;
		}

		/**
		 * Checks if the Security token has an appropriate role to edit the supplied Comment.
		 *
		 * @param  CommentInterface $comment
		 * @return boolean
		 */
		public function canEdit(CommentInterface $comment) {
		    // the comment owner can edit the comment whenever he want.
		    if ($comment instanceof SignedCommentInterface) {
		        if ($comment->getAuthor() == $this->tokenStorage->getToken()->getUser()) {
		            return true;
		        }
		    }
		    return parent::canEdit($comment);
		}

		/**
		 * Checks if the Security token is allowed to delete a specific Comment.
		 *
		 * @param  CommentInterface $comment
		 * @return boolean
		 */
		public function canDelete(CommentInterface $comment) {
		     // the comment owner can delete the comment
		    if ($comment instanceof SignedCommentInterface) {
		        if ($comment->getAuthor() == $this->tokenStorage->getToken()->getUser()) {
		            return true;
		        }
		    }
		    return parent::canDelete($comment);
		}

		/**
		 * Checks if the Security token is allowed to reply to a parent comment.
		 *
		 * @param  CommentInterface|null $parent
		 * @return boolean
		 */
		public function canReply(CommentInterface $parent = null) {
		   //this ligne allow all users to post new comments.
		    return parent::canCreate();
		}
	}