<?php
/**
 * @copyright Copyright (c) 2017 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Spreed;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class Participant {
	const OWNER = 1;
	const MODERATOR = 2;
	const USER = 3;
	const GUEST = 4;
	const USER_SELF_JOINED = 5;
	const GUEST_MODERATOR = 6;

	/** @var IDBConnection */
	protected $db;
	/** @var Room */
	protected $room;
	/** @var string */
	protected $user;
	/** @var int */
	protected $participantType;
	/** @var int */
	protected $lastPing;
	/** @var string */
	protected $sessionId;
	/** @var bool */
	protected $inCall;
	/** @var bool */
	private $isFavorite;

	/**
	 * @param IDBConnection $db
	 * @param Room $room
	 * @param string $user
	 * @param int $participantType
	 * @param int $lastPing
	 * @param string $sessionId
	 * @param bool $inCall
	 * @param bool $isFavorite
	 */
	public function __construct(IDBConnection $db, Room $room, $user, $participantType, $lastPing, $sessionId, $inCall, $isFavorite) {
		$this->db = $db;
		$this->room = $room;
		$this->user = $user;
		$this->participantType = $participantType;
		$this->lastPing = $lastPing;
		$this->sessionId = $sessionId;
		$this->inCall = $inCall;
		$this->isFavorite = $isFavorite;
	}

	public function getUser(): string {
		return $this->user;
	}

	public function getParticipantType(): int {
		return $this->participantType;
	}

	public function isGuest(): bool {
		return \in_array($this->participantType, [self::GUEST, self::GUEST_MODERATOR], true);
	}

	public function hasModeratorPermissions(bool $guestModeratorAllowed = true): bool {
		if (!$guestModeratorAllowed) {
			return \in_array($this->participantType, [self::OWNER, self::MODERATOR], true);
		}

		return \in_array($this->participantType, [self::OWNER, self::MODERATOR, self::GUEST_MODERATOR], true);
	}

	public function getLastPing(): int {
		return $this->lastPing;
	}

	public function getSessionId(): string {
		return $this->sessionId;
	}

	public function isInCall(): bool {
		return $this->inCall;
	}

	public function isFavorite(): bool {
		return $this->isFavorite;
	}

	public function setFavorite(bool $favor): bool {
		if (!$this->user) {
			return false;
		}

		$query = $this->db->getQueryBuilder();
		$query->update('talk_participants')
			->set('favorite', $query->createNamedParameter((int) $favor, IQueryBuilder::PARAM_INT))
			->where($query->expr()->eq('user_id', $query->createNamedParameter($this->user)))
			->andWhere($query->expr()->eq('room_id', $query->createNamedParameter($this->room->getId())));
		$query->execute();

		$this->isFavorite = $favor;
		return true;
	}
}
