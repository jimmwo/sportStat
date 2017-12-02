<?php

namespace SportStatisticsAnalyzer\Classes;

use SportStatisticsAnalyzer\Interfaces\IScore;

class FootballScore implements IScore
{
    private $stats;

    public function __construct($stats)
    {
        $this->stats = $stats;
    }

    /**
     * Рассчитывает итоговый счет матча
     * @param integer $firstTeamId идентификатор первой команды
     * @param integer $secondTeamId идентификатор второй команды
     * @return array
     */
    public function match($firstTeamId, $secondTeamId)
    {

        $averageScoredInLeagueFirstTeam = $this->averageTeamGoalsInLeagueByType($firstTeamId, 'scored');
        $averageScoredInLeagueSecondTeam = $this->averageTeamGoalsInLeagueByType($secondTeamId, 'scored');

        $ratioFirstTeam = $this->ratioTeam($firstTeamId);
        $ratioSecondTeam = $this->ratioTeam($secondTeamId);

        switch ($ratioFirstTeam > $ratioSecondTeam ) {
            case true:
                $correctPercentByPower = $this->getCorrectPercentByPower($ratioFirstTeam, $ratioSecondTeam);
                $match = $this->getMatchWithPercentRecounting($averageScoredInLeagueFirstTeam, $averageScoredInLeagueSecondTeam, $correctPercentByPower);
                break;

            case false:
                $correctPercentByPower = $this->getCorrectPercentByPower($ratioSecondTeam, $ratioFirstTeam);
                $match = array_reverse($this->getMatchWithPercentRecounting($averageScoredInLeagueSecondTeam, $averageScoredInLeagueFirstTeam, $correctPercentByPower));
                break;

            default:
                $match = $this->getMatchWithPercentRecounting($averageScoredInLeagueSecondTeam, $averageScoredInLeagueFirstTeam, 0);
                break;
        }

        return $match;
    }

    /**
     * Возвращает результаты всех возможных матчей
     * @return array
     */
    public function getAllMatchesResult()
    {
        $matches = [];
        foreach ($this->stats as $keyFirstTeam => $firstTeamStats) {
            foreach ($this->stats as $keySecondTeam => $secondTeamStats) {
                if ($firstTeamStats['name'] == $secondTeamStats['name']) continue;

                $matches[$firstTeamStats['name']][$secondTeamStats['name']] = implode(' : ', $this->match($keyFirstTeam, $keySecondTeam));
            }
        }

        return $matches;
    }

    /**
     * Рассчитывает среднее кол-во забитых или пропущеных голов в чемпионате по идентификатору команды и типу голов
     * @param integer $teamId идентификатор команды
     * @param string $type тип голов (пропущеный, забытый)
     * @throws \Exception Если нет статистики для заданого идентификатора или передан не верный тип голов
     * @return float
     */
    private function averageTeamGoalsInLeagueByType($teamId, $type)
    {
        if (empty($this->stats[$teamId])) {
            throw new \Exception("Статистика по команде # $teamId не найдена");
        }

        if (!isset($this->stats[$teamId]['goals'][$type])) {
            throw new \Exception("Неверный тип голов # $type");
        }

        return $this->stats[$teamId]['goals'][$type] / $this->stats[$teamId]['games'];
    }

    /**
     * Рассчитывает силу команды
     * @param integer $teamId идентификатор команды
     * @return float
     */
    private function ratioTeam($teamId)
    {
        $ratioTeamScored = $this->ratioTeamByGoalType($teamId, 'scored');
        $ratioTeamSkipped = $this->ratioTeamByGoalType($teamId, 'skiped');

        return $ratioTeamScored / $ratioTeamSkipped;
    }

    /**
     * Рассчитывает коэфициент голов в зависимости от типа
     * @param integer $teamId идентификатор команды
     * @param string $type тип голов (пропущеный, забытый)
     * @return float
     */
    private function ratioTeamByGoalType($teamId, $type)
    {
        $averageTeamSkipped = $this->averageTeamGoalsInLeagueByType($teamId, $type);
        $averageSkipped = $this->averageGoalsInLeagueByType($type);

        return $averageTeamSkipped / $averageSkipped;
    }

    /**
     * Рассчитывает среднее кол-во голов в чемпионате в зависимости от типа
     * @param string $type тип голов (пропущеный, забытый)
     * @return float
     */
    private function averageGoalsInLeagueByType($type)
    {
        $averageGames = $this->averageCountGamesInLeague();

        $skippedGoals = 0;
        foreach ($this->stats as $teamStats) {
            $skippedGoals += $teamStats['goals'][$type];
        }

        return $skippedGoals / $averageGames;
    }

    /**
     * Рассчитывает среднее кол-во игр в чемпионате
     * @return integer
     */
    private function averageCountGamesInLeague()
    {
        $countGames = 0;
        foreach ($this->stats as $teamStats) {
            $countGames += $teamStats['games'];
        }

        return $countGames / count($this->stats);
    }

    /**
     * Рассчитывает процент для корректировки итогового счета
     * @param float $strongerTeamRatio идентификатор команды
     * @param float $weakerTeamRatio идентификатор команды
     * @return float
     */
    private function getCorrectPercentByPower($strongerTeamRatio, $weakerTeamRatio)
    {
        $weakerTeamRatio = ($weakerTeamRatio == 0) ? 0.01 : $weakerTeamRatio;
        $correctPercentByPower = (($strongerTeamRatio * 100 / $weakerTeamRatio) - 100) / 2;

        return $correctPercentByPower;
    }

    /**
     * Вычисляет итоговый счет с учетом корректировочного процента
     * @param float $strongerTeam идентификатор команды
     * @param float $weakerTeam идентификатор команды
     * @param float $correctPercentByPower идентификатор команды
     * @return array
     */
    private function getMatchWithPercentRecounting($strongerTeam, $weakerTeam, $correctPercentByPower)
    {
        $strongerTeam += $correctPercentByPower * $strongerTeam / 100;
        $weakerTeam -= $correctPercentByPower * $weakerTeam / 100;

        return [$this->roundGoals($strongerTeam), $this->roundGoals($weakerTeam)];
    }

    /**
     * Округляет кол-во забитых голов до целого числа
     * @param float $goals кол-во забитых голов
     * @return integer
     */
    private function roundGoals($goals)
    {
        return round((($goals <= 0) ? 0 : $goals));
    }
}