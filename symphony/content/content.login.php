<?php

/**
 * @package content
 */

/**
 * The default Symphony login page that is shown to users who attempt
 * to access `SYMPHONY_URL` but are not logged in. This page has logic
 * to allow users to reset their passwords should they forget.
 */
class contentLogin extends HTMLPage
{
    public $failedLoginAttempt = false;

    public function __construct()
    {
        parent::__construct();

        $this->addHeaderToPage('Content-Type', 'text/html; charset=UTF-8');

        $this->Html->setElementStyle('html');
        $this->Html->setDTD('<!DOCTYPE html>');
        $this->Html->setAttribute('lang', Lang::get());
        $this->addElementToHead(new XMLElement('meta', null, array('charset' => 'UTF-8')), 0);
        $this->addElementToHead(new XMLElement('meta', null, array('http-equiv' => 'X-UA-Compatible', 'content' => 'IE=edge,chrome=1')), 1);
        $this->addElementToHead(new XMLElement('meta', null, array('name' => 'viewport', 'content' => 'width=device-width, initial-scale=1')), 2);

        $this->addStylesheetToHead(ASSETS_URL . '/css/symphony.min.css', 'screen', null, false);

        $this->setTitle(__('%1$s &ndash; %2$s', array(__('Login'), Symphony::Configuration()->get('sitename', 'general'))));

        $this->Body->setAttribute('id', 'login');

        Symphony::Profiler()->sample('Page template created', PROFILE_LAP);
    }

    public function build($context = null)
    {
        if ($context) {
            $this->_context = $context;
        }

        if (isset($_REQUEST['action'])) {
            $this->action();
        }

        $this->view();
    }

    public function view()
    {
        if (isset($this->_context[0]) && in_array(strlen($this->_context[0]), array(6, 8, 16))) {
            if (!$this->__loginFromToken($this->_context[0])) {
                if (Administration::instance()->isLoggedIn()) {
                    // Redirect to the Author's profile. RE: #1801
                    redirect(SYMPHONY_URL . '/system/authors/edit/' . Symphony::Author()->get('id') . '/reset-password/');
                }
            }
        }

        $this->Form = Widget::Form(SYMPHONY_URL . '/login/', 'post');
        $this->Form->setAttribute('class', 'frame');
        $this->Form->appendChild(new XMLElement('h1', Symphony::Configuration()->get('sitename', 'general')));

        $fieldset = new XMLElement('fieldset');

        // Display retrieve password UI
        if (isset($this->_context[0]) && $this->_context[0] == 'retrieve-password') {
            $this->Form->setAttribute('action', SYMPHONY_URL.'/login/retrieve-password/');

            // Successful reset
            if (isset($this->_email_sent) && $this->_email_sent) {
                $fieldset->appendChild(new XMLElement('p', __('An email containing a customised login link has been sent to %s. It will expire in 2 hours.', array(
                    '<code>' . $this->_email_sent_to . '</code>')
                )));
                $fieldset->appendChild(new XMLElement('p', Widget::Anchor(__('Login'), SYMPHONY_URL.'/login/', null)));
                $this->Form->appendChild($fieldset);

                // Default, get the email address for reset
            } else {
                $fieldset->appendChild(new XMLElement('p', __('Enter your email address or username to be sent further instructions for logging in.')));

                $label = Widget::Label(__('Email Address or Username'));
                $label->appendChild(Widget::Input('email', General::sanitize($_POST['email']), 'text', array('autofocus' => 'autofocus')));

                if (isset($this->_email_sent) && !$this->_email_sent) {
                    $label = Widget::Error($label, __('Unfortunately no account was found using this information.'));
                } else {
                    // Email exception
                    if (isset($this->_email_error) && $this->_email_error) {
                        $label = Widget::Error($label, __('This Symphony instance has not been set up for emailing, %s', array('<code>' . $this->_email_error . '</code>')));
                    }
                }

                $fieldset->appendChild($label);

                $this->Form->appendChild($fieldset);

                $div = new XMLElement('div', null, array('class' => 'actions'));
                $div->appendChild(
                    new XMLElement('button', __('Send Email'), array('name' => 'action[reset]', 'type' => 'submit', 'accesskey' => 's'))
                );
                $div->appendChild(
                    Widget::Anchor(__('Cancel'), SYMPHONY_URL.'/login/', null, 'action-link')
                );
                $this->Form->appendChild($div);
            }

            // Normal login
        } else {

            $fieldset->appendChild(new XMLElement('legend', __('Login'), array('role' => 'heading')));

            // Display error message
            if ($this->failedLoginAttempt) {
                $p = new XMLElement('p');
                $p = Widget::Error($p, __('The login details provided are incorrect.'));
                $fieldset->appendChild($p);
            }

            // Username
            $label = Widget::Label(__('Username'));
            $username = Widget::Input('username', isset($_POST['username']) ? General::sanitize($_POST['username']) : null);

            if (!$this->failedLoginAttempt) {
                $username->setAttribute('autofocus', 'autofocus');
            }

            $label->appendChild($username);

            if (isset($_POST['action'], $_POST['action']['login']) && empty($_POST['username'])) {
                $username->setAttribute('autofocus', 'autofocus');
                $label = Widget::Error($label, __('No username was entered.'));
            }

            $fieldset->appendChild($label);

            // Password
            $label = Widget::Label(__('Password'));
            $password = Widget::Input('password', null, 'password');
            $label->appendChild($password);

            if (isset($_POST['action'], $_POST['action']['login']) && empty($_POST['password'])) {
                $password->setAttribute('autofocus', 'autofocus');
                $label = Widget::Error($label, __('No password was entered.'));
            } elseif ($this->failedLoginAttempt) {
                $password->setAttribute('autofocus', 'autofocus');
            }

            $fieldset->appendChild($label);
            $this->Form->appendChild($fieldset);

            // Actions
            $div = new XMLElement('div', null, array('class' => 'actions'));
            $div->appendChild(
                new XMLElement('button', __('Login'), array('name' => 'action[login]', 'type' => 'submit', 'accesskey' => 'l'))
            );
            $div->appendChild(
                Widget::Anchor(__('Retrieve password?'), SYMPHONY_URL.'/login/retrieve-password/', null, 'action-link')
            );
            $this->Form->appendChild($div);

            if (isset($this->_context['redirect'])) {
                $this->Form->appendChild(
                    Widget::Input('redirect', SYMPHONY_URL . General::sanitize($this->_context['redirect']), 'hidden')
                );
            }
        }

        $this->Body->appendChild($this->Form);
    }

    public function action()
    {
        if (isset($_POST['action'])) {
            $actionParts = array_keys($_POST['action']);
            $action = end($actionParts);

            // Login Attempted
            if ($action == 'login') {
                if (empty($_POST['username']) || empty($_POST['password']) || !Administration::instance()->login($_POST['username'], $_POST['password'])) {
                    /**
                     * A failed login attempt into the Symphony backend
                     *
                     * @delegate AuthorLoginFailure
                     * @since Symphony 2.2
                     * @param string $context
                     * '/login/'
                     * @param string $username
                     *  The username of the Author who attempted to login.
                     */
                    Symphony::ExtensionManager()->notifyMembers('AuthorLoginFailure', '/login/', array('username' => Symphony::Database()->cleanValue($_POST['username'])));
                    $this->failedLoginAttempt = true;
                } else {
                    /**
                     * A successful login attempt into the Symphony backend
                     *
                     * @delegate AuthorLoginSuccess
                     * @since Symphony 2.2
                     * @param string $context
                     * '/login/'
                     * @param string $username
                     *  The username of the Author who logged in.
                     */
                    Symphony::ExtensionManager()->notifyMembers('AuthorLoginSuccess', '/login/', array('username' => Symphony::Database()->cleanValue($_POST['username'])));

                    isset($_POST['redirect']) ? redirect($_POST['redirect']) : redirect(SYMPHONY_URL . '/');
                }

                // Reset of password requested
            } elseif ($action == 'reset') {
                $author = Symphony::Database()->fetchRow(0, sprintf("
                        SELECT `id`, `email`, `first_name`
                        FROM `tbl_authors`
                        WHERE `email` = '%1\$s' OR `username` = '%1\$s'
                    ", Symphony::Database()->cleanValue($_POST['email'])
                ));

                if (!empty($author)) {
                    // Delete all expired tokens
                    Symphony::Database()->delete('tbl_forgotpass', sprintf("
                        `expiry` < %d", DateTimeObj::getGMT('U')
                    ));

                    // Attempt to retrieve the token that is not expired for this Author ID,
                    // otherwise generate one.
                    if (!$token = Symphony::Database()->fetchVar('token', 0, sprintf("
                            SELECT `token`
                            FROM `tbl_forgotpass`
                            WHERE `expiry` > %d AND `author_id` = %d
                        ",
                        DateTimeObj::getGMT('U'),
                        $author['id']
                    ))) {
                        // More secure password token generation
                        if (function_exists('openssl_random_pseudo_bytes')) {
                            $seed = openssl_random_pseudo_bytes(16);
                        } else {
                            $seed = mt_rand();
                        }

                        $token = substr(SHA1::hash($seed), 0, 16);

                        Symphony::Database()->insert(array(
                            'author_id' => $author['id'],
                            'token' => $token,
                            'expiry' => DateTimeObj::getGMT('c', time() + (120 * 60))
                        ), 'tbl_forgotpass');
                    }

                    try {
                        $email = Email::create();

                        $email->recipients = $author['email'];
                        $email->subject = __('New Symphony Account Password');
                        $email->text_plain = __('Hi %s,', array($author['first_name'])) . PHP_EOL .
                                __('A new password has been requested for your account. Login using the following link, and change your password via the Authors area:') . PHP_EOL .
                                PHP_EOL . '    ' . SYMPHONY_URL . "/login/{$token}/" . PHP_EOL . PHP_EOL .
                                __('It will expire in 2 hours. If you did not ask for a new password, please disregard this email.') . PHP_EOL . PHP_EOL .
                                __('Best Regards,') . PHP_EOL .
                                __('The Symphony Team');

                        $email->send();
                        $this->_email_sent = true;
                        $this->_email_sent_to = $author['email']; // Set this so we can display a customised message
                    } catch (Exception $e) {
                        $this->_email_error = General::unwrapCDATA($e->getMessage());
                        Symphony::Log()->pushExceptionToLog($e, true);
                    }

                    /**
                     * When a password reset has occurred and after the Password
                     * Reset email has been sent.
                     *
                     * @delegate AuthorPostPasswordResetSuccess
                     * @since Symphony 2.2
                     * @param string $context
                     * '/login/'
                     * @param integer $author_id
                     *  The ID of the Author who requested the password reset
                     */
                    Symphony::ExtensionManager()->notifyMembers('AuthorPostPasswordResetSuccess', '/login/', array('author_id' => $author['id']));
                } else {

                    /**
                     * When a password reset has been attempted, but Symphony doesn't
                     * recognise the credentials the user has given.
                     *
                     * @delegate AuthorPostPasswordResetFailure
                     * @since Symphony 2.2
                     * @param string $context
                     * '/login/'
                     * @param string $email
                     *  The sanitised Email of the Author who tried to request the password reset
                     */
                    Symphony::ExtensionManager()->notifyMembers('AuthorPostPasswordResetFailure', '/login/', array('email' => Symphony::Database()->cleanValue($_POST['email'])));

                    $this->_email_sent = false;
                }
            }
        }
    }

    public function __loginFromToken($token)
    {
        // If token is invalid, return to login page
        if (!Administration::instance()->loginFromToken($token)) {
            return false;
        }

        // If token is valid and is an 8 char shortcut
        if (!in_array(strlen($token), array(6, 16))) {
            redirect(SYMPHONY_URL . '/'); // Regular token-based login
        }

        return false;
    }
}
