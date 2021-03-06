<?php

namespace Ihsan\UsernameGenerator;

use Ihsan\UsernameGenerator\Generator\BalineseUsernameGenerator;
use Ihsan\UsernameGenerator\Generator\GenericUsernameGenerator;
use Ihsan\UsernameGenerator\Generator\IslamicUsernameGenerator;
use Ihsan\UsernameGenerator\Generator\ShortUsernameGenerator;
use Ihsan\UsernameGenerator\Generator\WesternUsernameGenerator;
use Ihsan\UsernameGenerator\Repository\UsernameInterface;
use Ihsan\UsernameGenerator\Repository\UsernameRepositoryInterface;
use Ihsan\UsernameGenerator\Util\DateGenerator;
use Ihsan\UsernameGenerator\Util\UniqueNumberGenerator;
use Ihsan\UsernameGenerator\Util\CharacterShifter;

/**
 * @author Muhammad Surya Ihsanuddin <surya.kejawen@gmail.com>
 */
class UsernameFactory
{
    /**
     * @var UsernameRepositoryInterface
     */
    private $repository;

    /**
     * @var CharacterShifter
     */
    private $shifter;

    /**
     * @var string
     */
    private $class;

    /**
     * @var array
     */
    private $characters;

    /**
     * @var array
     */
    private $dates;

    /**
     * @var int
     */
    private $hit = 0;

    /**
     * @param UsernameRepositoryInterface $usernameRepository
     * @param CharacterShifter             $shifter
     * @param string                      $usernameClass
     */
    public function __construct(UsernameRepositoryInterface $usernameRepository, CharacterShifter $shifter, $usernameClass)
    {
        $this->repository = $usernameRepository;
        $this->shifter = $shifter;
        $this->class = $usernameClass;
    }

    /**
     * @param string    $fullName
     * @param \DateTime $birthday
     * @param int       $characterLimit
     * @param int       $maxUsernamePerPrefix
     *
     * @return null|string
     */
    public function generate($fullName, \DateTime $birthday, $characterLimit = 8, $maxUsernamePerPrefix = 1000)
    {
        $fullName = strtoupper($fullName);
        $characters = array();
        $isShort = false;

        if ($characterLimit > strlen($fullName)) {
            $shortGenerator = new ShortUsernameGenerator($this->shifter);
            $characters = array_merge($characters, $shortGenerator->genarate($fullName, $characterLimit));

            $isShort = true;
        }

        if (!$isShort) {
            $balineseGenerator = new BalineseUsernameGenerator($this->shifter);
            if (-1 !== $balineseGenerator->isReservedName($fullName)) {
                $characters = array_merge($characters, $balineseGenerator->genarate($fullName, $characterLimit));
            }

            $islamicGenerator = new IslamicUsernameGenerator($this->shifter);
            if (-1 !== $islamicGenerator->isReservedName($fullName)) {
                $characters = array_merge($characters, $islamicGenerator->genarate($fullName, $characterLimit));
            }

            $westernGenerator = new WesternUsernameGenerator($this->shifter);
            if (-1 !== $westernGenerator->isReservedName($fullName)) {
                $characters = array_merge($characters, $westernGenerator->genarate($fullName, $characterLimit));
            }

            $genericGenerator = new GenericUsernameGenerator($this->shifter);
            $characters = array_merge($characters, $genericGenerator->genarate($fullName, $characterLimit));
        }

        $dates = DateGenerator::generate($birthday);

        $this->characters = $characters;
        $this->dates = $dates;

        $realUsername = null;
        foreach ($characters as $character) {
            foreach ($dates as $date) {
                $username = sprintf('%s%s', $character, $date);
                if (!$this->repository->isExist($username) && $maxUsernamePerPrefix >= $this->repository->countUsage($character)) {
                    $realUsername = $username;

                    break;
                } else {
                    ++$this->hit;
                }
            }

            if ($realUsername) {
                break;
            }
        }

        if (!$realUsername) {
            foreach ($characters as $character) {
                $flag = true;
                while ($flag) {
                    $username = sprintf('%s%s', $character, UniqueNumberGenerator::generate());
                    if (!$this->repository->isExist($username) && $maxUsernamePerPrefix >= $this->repository->countUsage($character)) {
                        $realUsername = $username;

                        $flag = false;
                    } else {
                        ++$this->hit;
                    }
                }

                if (!$flag) {
                    break;
                }
            }
        }

        /** @var UsernameInterface $user */
        $user = new $this->class();
        $user->setFullName($fullName);
        $user->setBirthDay($birthday);
        $user->setUsername($realUsername);

        $this->repository->save($user);

        return $realUsername;
    }

    /**
     * @return array
     */
    public function getAllCharacter()
    {
        return $this->characters;
    }

    /**
     * @return array
     */
    public function getAllDates()
    {
        return $this->dates;
    }

    /**
     * @return int
     */
    public function getTotalHit()
    {
        return $this->hit;
    }

    /**
     * @return int
     */
    public function getTotalSuggestion()
    {
        return ($this->getTotalCharacter() * $this->getTotalDate()) + ($this->getTotalCharacter() * $this->getTotalNumber());
    }

    /**
     * @return int
     */
    public function getTotalCharacter()
    {
        return count($this->characters);
    }

    /**
     * @return int
     */
    public function getTotalDate()
    {
        return count($this->dates);
    }

    /**
     * @return int
     */
    public function getTotalNumber()
    {
        return 10000;
    }
}
