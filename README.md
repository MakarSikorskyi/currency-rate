To execute do the following:  

 ```
   PHP 8.2
   Redis-7.2
   RabbitMQ-3.13
 ```

Настроить  `.env` и `composer install`


1. Значение курса и разница с предыдущим торговым днем первый параметр дата в формате "d/m/Y" второй парамет код валюты третий (необязательный) код валюты

    ```
    php yii currency/fetch-rates 20/06/2024 EUR USD
    ```

2. Наполнение очереди первый парамет код валюты второй (необязательный) код валюты

    ```
    php yii currency/queue-rates USD EUR 
    ```

3. Запуск воркера для обработки сообщений 

    ```
     php worker.php   
    ```
