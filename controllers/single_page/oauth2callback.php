<?php
namespace Concrete\Package\PageRankingListGaV4\Controller\SinglePage;

use PageController;
use Core;
use Page;
use Permissions;
use Concrete\Core\Http\Response;
use Concrete\Core\Http\ResponseFactoryInterface;
use Concrete\Core\Entity\File\File as FileEntity;
use Concrete\Core\File\File;

class Oauth2callback extends PageController
{
    public function on_start(){

        session_start();
        
        // Create the client object and set the authorization configuration
        // from the client_secrets.json you downloaded from the Developers Console.
        $client = new \Google_Client();
        $api_file = Core::make('site')->getSite()->getAttribute('google_api_json');
        $client->setAuthConfig($_SERVER['DOCUMENT_ROOT'] . $api_file->getRelativePath());
        $client->setRedirectUri(BASE_URL . '/index.php/oauth2callback');
        $client->addScope(\Google_Service_Analytics::ANALYTICS_READONLY);

        // Handle authorization flow from the server.
        if (!isset($_GET['code'])) {
          $auth_url = $client->createAuthUrl();
          $rf = $this->app->make(ResponseFactoryInterface::class);
          $response = $rf->redirect(filter_var($auth_url, FILTER_SANITIZE_URL), Response::HTTP_TEMPORARY_REDIRECT);
          $response->send();
          exit();
//          header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
        } else {

          $client->authenticate($_GET['code']);
          $_SESSION['access_token_ga'] = $client->getAccessToken();
          $redirect_uri = $_SERVER['HTTP_REFERER'];
          $rf = $this->app->make(ResponseFactoryInterface::class);
          $response = $rf->redirect(filter_var($redirect_uri, FILTER_SANITIZE_URL), Response::HTTP_TEMPORARY_REDIRECT);
          $response->send();
          exit();
//          header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
        }

    }
    public function view()
    {
        $this->set('aaa','bbb');
    }
}
