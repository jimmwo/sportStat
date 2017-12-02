<?php

namespace SportStatisticsAnalyzer\Interfaces;

interface IScore
{
    /**
     * Рассчитывает кол-во забитых голов друг-другу
     * @param integer $firstTeamId идентификатор первой команды
     * @param integer $secondTeamId идентификатор второй команды
     * @return array
     */
    public function match($firstTeamId, $secondTeamId);

    /**
     * Возвращает результаты всех возможных матчей
     * @return array
     */
    public function getAllMatchesResult();
}