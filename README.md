# Описание
Тестовое задание #1
Реализация в один файл

# Требования для запуска
- php 7.1 и выше

# Конфигурация и запуск
Запуск скрипта:

`php check_seo_info.php -f seo_test.csv -c test=seo;`

где:
- -f - путь до csv файла с seo-данными;
- -c - строка с необходимыми cookie-данными, передаваемыми в seo-адреса

# Отчет

Отчет формируется в консоле запуска (для сохранения отчета в конец sh-файла запуска можно добавить ` > report.txt`)

Пример отчета

```sh
Check: 1/4
URL: https://www.sports.ru
Check title........................PASSED
Check description........................PASSED

Check: 2/4
URL: https://www.sports.ru/basketball/
Check title........................PASSED
Check description........................PASSED

Check: 3/4
URL: https://www.sports.ru/tennis/
Check title........................PASSED
Check description........................FAILED

Check: 4/4
URL: https://www.sports.ru/hockey/
Check title........................FAILED
Check description........................PASSED

================================ CHECKS COMPLETE ! ================================


############################### TITLE CHECKS REPORT ###############################
TITLE checks count: 4
Passed checks: 3 (75%)
Failed checks: 1


Failed check: title
URL: https://www.sports.ru/hockey/
Expected: Хоккей России и мира, новости хоккея, КХЛ, онлайн трансляции, видео голов, трансферы, результаты, статистика, таблицы
Actual: Хоккей России и мира, новости хоккея, КХЛ, НХЛ, Евротур, онлайн трансляции, видео голов, трансферы, результаты, статистика, таблицы
################################################################


############################### DESCRIPTION CHECKS REPORT ###############################
DESCRIPTION checks count: 4
Passed checks: 3 (75%)
Failed checks: 1


Failed check: description
URL: https://www.sports.ru/tennis/
Expected: Свежие новости тенниса, онлайн трансляции, статистика.
Actual: Свежие новости тенниса, онлайн трансляции, статистика, видео, рейтинги, турниры Большого шлема. Блоги теннисистов и тренеров, форумы болельщиков
################################################################

```
