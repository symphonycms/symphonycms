<?php
/**
 * @package content
 */
/**
 * The default Logout page will redirect the user
 * to the Homepage of `URL`
 */
class contentLogout extends HTMLPage
{
    public function build(array $context = [])
    {
        $this->view();
    }

    public function view()
    {
        Administration::instance()->logout();
        $redirectUrl = URL;
        /**
         * A successful logout attempt into the Symphony backend
         *
         * @delegate AuthorLogout
         * @since Symphony 3.0.0
         * @param string $context
         * '/logout/'
         * @param integer $author_id
         *  The ID of Author ID that is about to be deleted
         * @param Author $author
         *  The Author object.
         * @param string $redirect_url
         *  The url to which the author will be redirected
         */
        Symphony::ExtensionManager()->notifyMembers('AuthorLogout', '/logout/', [
            'author' => Symphony::Author(),
            'author_id' => Symphony::Author()->get('id'),
            'redirect_url' => &$redirectUrl,
        ]);
        redirect($redirectUrl);
    }
}
