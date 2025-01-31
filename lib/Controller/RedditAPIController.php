<?php
/**
 * Nextcloud - reddit
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
 * @copyright Julien Veyssier 2020
 */

namespace OCA\Reddit\Controller;

use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\IConfig;
use OCP\IRequest;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;

use OCA\Reddit\Service\RedditAPIService;
use OCA\Reddit\AppInfo\Application;
use OCP\IURLGenerator;
use OCP\PreConditionNotMetException;

class RedditAPIController extends Controller {

	/**
	 * @var IConfig
	 */
	private $config;
	/**
	 * @var RedditAPIService
	 */
	private $redditAPIService;
	/**
	 * @var string|null
	 */
	private $userId;
	/**
	 * @var string
	 */
	private $accessToken;
	/**
	 * @var IURLGenerator
	 */
	private $urlGenerator;

	public function __construct(string $appName,
								IRequest $request,
								IConfig $config,
								IURLGenerator $urlGenerator,
								RedditAPIService $redditAPIService,
								?string $userId) {
		parent::__construct($appName, $request);
		$this->config = $config;
		$this->redditAPIService = $redditAPIService;
		$this->userId = $userId;
		$this->accessToken = $this->config->getUserValue($this->userId, Application::APP_ID, 'token');
		$this->urlGenerator = $urlGenerator;
	}

	/**
	 * get notification list
	 * @NoAdminRequired
	 *
	 * @param string|null $after
	 * @return DataResponse
	 * @throws PreConditionNotMetException
	 */
	public function getNotifications(?string $after = null): DataResponse {
		if ($this->accessToken === '') {
			return new DataResponse(null, 400);
		}
		$result = $this->redditAPIService->getNotifications($this->userId, $after);
		if (!isset($result['error'])) {
			$response = new DataResponse($result);
		} else {
			$response = new DataResponse($result, 401);
		}
		return $response;
	}

	/**
	 * get repository avatar
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param ?string $username
	 * @param string|null $subreddit
	 * @return DataDisplayResponse|RedirectResponse
	 * @throws PreConditionNotMetException
	 */
	public function getAvatar(?string $username = null, string $subreddit = null) {
		$avatarContent = $this->redditAPIService->getAvatar($this->userId, $username, $subreddit);
		if ($avatarContent !== '') {
			$response = new DataDisplayResponse($avatarContent);
			$response->cacheFor(60 * 60 * 24);
			return $response;
		} else {
			$fallbackAvatarUrl = $this->urlGenerator->linkToRouteAbsolute('core.GuestAvatar.getAvatar', ['guestName' => $username ?? $subreddit, 'size' => 44]);
			return new RedirectResponse($fallbackAvatarUrl);
		}
	}
}
