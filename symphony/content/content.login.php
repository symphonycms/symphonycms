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
    private $_email_sent;
    private $_email_error;
    private $_email_sent_to;

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
        $this->addElementToHead(new XMLElement('meta', null, array('name' => 'robots', 'content' => 'noindex,nofollow,noarchive')), 3);

        parent::addStylesheetToHead(ASSETS_URL . '/css/symphony.min.css', 'screen', null, false);

        $this->setTitle(__('%1$s &ndash; %2$s', array(__('Login'), Symphony::Configuration()->get('sitename', 'general'))));

        $this->Body->setAttribute('id', 'login');

        Symphony::Profiler()->sample('Page template created', PROFILE_LAP);
    }

    public function addScriptToHead($path, $position = null, $duplicate = true)
    {
        // Prevent script injection by extensions
    }

    public function addStylesheetToHead($path, $type = 'screen', $position = null, $duplicate = true)
    {
        // Prevent stylesheet injection by extensions
    }

    /**
     * The Login page has /action/sub-action/token/ context.
     * /login/
     * /login/retrieve-password/
     * /login/{$token}/
     * /login/{$token}/reset-password/
     *
     * @param array $context
     * @param array $parts
     * @return array
     */
    public function parseContext(array &$context, array $parts)
    {
        if (isset($parts[1])) {
            if ($parts[1] === 'retrieve-password') {
                $context['action'] = $parts[1];
            } else {
                $context['token'] = $parts[1];
            }
        }
        if (empty($context['action'])) {
            if (isset($parts[2])) {
                $context['action'] = $parts[2];
            } else {
                $context['action'] = $parts[0]; // should always be login
            }
        }
    }

    public function build(array $context = [])
    {
        if (Symphony::isLoggedIn()) {
            redirect(APPLICATION_URL);
        }

        parent::build($context);

        if (isset($_REQUEST['action'])) {
            $this->action();
        }

        $this->view();
    }

    public function view()
    {
        if (isset($this->_context['token']) && $this->_context['action'] === 'reset-password') {
            if (Administration::instance()->loginFromToken($this->_context['token'])) {
                if (Administration::instance()->isLoggedIn()) {
                    // Redirect to the Author's profile. RE: #1801
                    redirect(SYMPHONY_URL . '/system/authors/edit/' . Symphony::Author()->get('id') . '/reset-password/');
                }
            }
            // Somehow, the login failed...
            redirect(SYMPHONY_URL . '/login/');
        } elseif (isset($this->_context['token']) && $this->_context['action'] === 'login') {
            if (Administration::instance()->loginFromToken($this->_context['token'])) {
                if (Administration::instance()->isLoggedIn()) {
                    // Regular token-based login
                    redirect(SYMPHONY_URL . '/');
                }
            }
        } elseif (isset($this->_context['token'])) {
            // Token with invalid action
            redirect(SYMPHONY_URL . '/login/');
        }

        $this->Form = Widget::Form(SYMPHONY_URL . '/login/', 'post');
        $this->Form->setAttribute('class', 'frame');
        $this->Form->appendChild(new XMLElement('h1', Symphony::Configuration()->get('sitename', 'general')));

        $fieldset = new XMLElement('fieldset');

        // Display retrieve password UI
        if ($this->_context['action'] == 'retrieve-password') {
            $this->Form->setAttribute('action', SYMPHONY_URL.'/login/retrieve-password/');

            // Successful reset
            if ($this->_email_sent) {
                $fieldset->appendChild(new XMLElement('p', __('An email containing a customised login link has been sent to %s. It will expire in 2 hours.', array(
                    '<code>' . General::sanitize($this->_email_sent_to) . '</code>')
                )));
                $fieldset->appendChild(new XMLElement('p', Widget::Anchor(__('Login'), SYMPHONY_URL.'/login/', null)));
                $this->Form->appendChild($fieldset);

                // Default, get the email address for reset
            } else {
                $fieldset->appendChild(new XMLElement('p', __('Enter your email address or username to be sent further instructions for logging in.')));

                $label = Widget::Label(__('Email Address or Username'));
                $label->appendChild(Widget::Input('email', General::sanitize($_POST['email']), 'text', array('autofocus' => 'autofocus')));

                if ($this->_email_sent === false) {
                    $label = Widget::Error($label, __('Unfortunately no account was found using this information.'));
                } else {
                    // Email exception
                    if ($this->_email_error) {
                        $label = Widget::Error($label, __('This Symphony instance has not been set up for emailing, %s', array('<code>' . General::sanitize($this->_email_error) . '</code>')));
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
                if (empty($_POST['username']) ||
                    empty($_POST['password']) ||
                    !Administration::login($_POST['username'], $_POST['password'])) {
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
                    Symphony::ExtensionManager()->notifyMembers(
                        'AuthorLoginFailure',
                        '/login/',
                        ['username' => $_POST['username']]
                    );
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
                    Symphony::ExtensionManager()->notifyMembers(
                        'AuthorLoginSuccess',
                        '/login/',
                        ['username' => $_POST['username']]
                    );

                    isset($_POST['redirect']) ? redirect($_POST['redirect']) : redirect(SYMPHONY_URL . '/');
                }

                // Reset of password requested
            } elseif ($action == 'reset') {
                $author = Symphony::Database()
                    ->select(['id', 'email', 'first_name'])
                    ->from('tbl_authors')
                    ->where(['or' => [
                        'email' => $_POST['email'],
                        'username' => $_POST['email'],
                    ]])
                    ->execute()
                    ->next();

                if (!empty($author)) {
                    // Delete all expired tokens
                    Symphony::Database()
                        ->delete('tbl_forgotpass')
                        ->where(['expiry' => ['<' => DateTimeObj::getGMT('c')]])
                        ->execute();

                    // Attempt to retrieve the token that is not expired for this Author ID,
                    // otherwise generate one.
                    $token = Symphony::Database()
                        ->select(['token'])
                        ->from('tbl_forgotpass')
                        ->where(['expiry' => ['>' => DateTimeObj::getGMT('c')]])
                        ->where(['author_id' => $author['id']])
                        ->execute()
                        ->string('token');

                    if (!$token) {
                        $token = Cryptography::randomBytes();

                        Symphony::Database()
                            ->insert('tbl_forgotpass')->values([
                                'author_id' => $author['id'],
                                'token' => $token,
                                'expiry' => DateTimeObj::getGMT('c', time() + (120 * 60))
                            ])
                            ->execute();
                    }

                    try {
                        $email = Email::create();

                        $email->recipients = $author['email'];
                        $email->subject = __('New Symphony Account Password');
                        $email->text_plain = __('Hi %s,', array($author['first_name'])) . PHP_EOL .
                                __('A new password has been requested for your account. Login using the following link, and change your password via the Authors area:') . PHP_EOL .
                                PHP_EOL . '    ' . SYMPHONY_URL . "/login/{$token}/reset-password/" . PHP_EOL . PHP_EOL .
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
                    Symphony::ExtensionManager()->notifyMembers('AuthorPostPasswordResetFailure', '/login/', array('email' => $_POST['email']));

                    $this->_email_sent = false;
                }
            }
        }
    }
}
