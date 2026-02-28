# GreenArt CRM

GreenArt — CRM-система для компании по озеленению. Связывает клиентов, садовников, администраторов и бухгалтеров в едином пространстве: заказы, визиты, отчёты, зарплаты, чаты и объявления.

## Технологический стек

- **PHP** 8.2+
- **Laravel** 12
- **Laravel Sanctum** — аутентификация через токены
- **Laravel Reverb** — WebSocket для чатов
- **SQLite** / **PostgreSQL** — БД
- **Vite** — сборка фронтенда

## Роли системы

| Роль         | Константа   | Описание                                           |
|--------------|-------------|---------------------------------------------------|
| Клиент       | `client`    | Создаёт заказы, видит отчёты, общается в чатах     |
| Садовник     | `worker`    | Выполняет задачи, сдаёт отчёты, фиксирует визиты  |
| Администратор| `admin`     | Управляет всем: заказы, пользователи, визиты, ЗП   |
| Бухгалтер    | `accountant`| Контролирует финансы, классифицирует заказы          |

## Основные сущности

| Таблица               | Описание                                                   |
|-----------------------|-----------------------------------------------------------|
| `users`               | Пользователи с ролями и дневной ставкой (для садовников)   |
| `orders`              | Заказы: описание, тип оплаты, статус, `completed_at`       |
| `order_photos`        | Фотографии к заказам                                       |
| `order_reports`       | Отчёты садовника по заказу (дата, фото, `completed_at`)    |
| `order_report_photos` | Фотографии к отчётам                                       |
| `work_visits`         | Визиты садовника к клиенту: дата, одобрение администратором |
| `salary_adjustments`  | Штрафы и надбавки (penalty / bonus)                        |
| `announcements`       | Объявления с аудиторией (all / clients / workers)           |
| `announcement_photos` | Фотографии к объявлениям                                    |
| `chats`               | Чаты (general / order / private)                            |
| `chat_messages`       | Сообщения с поддержкой файлов                               |

## Жизненный цикл заказа

```
pending → assigned → in_progress → done
                                  ↗ cancelled
```

1. Клиент создаёт заявку с описанием и фото.
2. Админ классифицирует: `included` (подписка) или `extra` (разовая + сумма).
3. Админ назначает садовника.
4. Садовник выполняет работу, сдаёт отчёт с фото.
5. При `is_completed = true` статус → `done`, `completed_at` заполняется автоматически.

## Визиты и одобрение

- Садовник создаёт визит к заказу (`POST /api/worker/visits`).
- Админ просматривает визиты, может фильтровать по одобренным/неодобренным.
- Админ одобряет визит (`POST /api/admin/visits/{id}/approve`) — подтверждает присутствие.
- Админ может редактировать визит (`PATCH /api/admin/visits/{id}`).

## Зарплаты

**Формула расчёта:**
```
total_salary = (дни_работы × дневная_ставка) + extras_total + bonuses_total - penalties_total
```

- `GET /api/admin/workers/{id}/salary?from=...&to=...` — ЗП одного садовника.
- `GET /api/admin/salaries?from=...&to=...` — ЗП всех садовников за период.
- Штрафы/надбавки управляются через `/api/admin/salary-adjustments` (CRUD).

## API — обзор маршрутов

### Аутентификация
| Метод | Путь                | Описание            |
|-------|---------------------|---------------------|
| POST  | `/api/register`     | Регистрация         |
| POST  | `/api/auth`         | Вход (логин/email)  |
| GET   | `/api/me`           | Текущий пользователь|
| PUT   | `/api/user`         | Обновить профиль    |
| POST  | `/api/logout`       | Выход               |

### Заказы (все авторизованные)
| Метод  | Путь                  | Описание         |
|--------|-----------------------|------------------|
| GET    | `/api/orders`         | Список (фильтры) |
| POST   | `/api/orders`         | Создать           |
| GET    | `/api/orders/{id}`    | Просмотр          |
| PUT    | `/api/orders/{id}`    | Обновить          |
| DELETE | `/api/orders/{id}`    | Удалить           |

### Админ (`/api/admin/`)
| Метод  | Путь                                       | Описание                            |
|--------|---------------------------------------------|-------------------------------------|
| GET    | `/admin/users`                              | Все пользователи                    |
| POST   | `/admin/users`                              | Создать пользователя                |
| PATCH  | `/admin/users/{id}`                         | Обновить пользователя               |
| GET    | `/admin/clients`                            | Список клиентов                     |
| GET    | `/admin/workers`                            | Список садовников                   |
| POST   | `/admin/clients/{id}/default-worker`        | Назначить садовника по умолчанию    |
| PATCH  | `/admin/orders/{id}/classification`         | Классифицировать заказ              |
| POST   | `/admin/orders/{id}/assign-worker`          | Назначить садовника на заказ        |
| GET    | `/admin/visits`                             | Все визиты (фильтры)               |
| PATCH  | `/admin/visits/{id}`                        | Редактировать визит                 |
| POST   | `/admin/visits/{id}/approve`                | Одобрить визит                      |
| GET    | `/admin/workers/{id}/salary`                | ЗП одного садовника за период       |
| GET    | `/admin/salaries`                           | ЗП всех садовников за период        |
| GET    | `/admin/salary-adjustments`                 | Штрафы/надбавки (список)            |
| POST   | `/admin/salary-adjustments`                 | Создать штраф/надбавку              |
| DELETE | `/admin/salary-adjustments/{id}`            | Удалить штраф/надбавку              |

### Садовник (`/api/worker/`)
| Метод  | Путь                              | Описание                     |
|--------|-----------------------------------|------------------------------|
| GET    | `/worker/tasks`                   | Мои задачи (фильтры)         |
| GET    | `/worker/reports`                 | Мои отчёты за дату            |
| POST   | `/worker/tasks/{order_id}/report` | Сдать отчёт по задаче         |
| GET    | `/worker/visits`                  | Мои визиты                    |
| POST   | `/worker/visits`                  | Создать визит                 |
| DELETE | `/worker/visits/{id}`             | Удалить визит                 |

### Объявления
| Метод  | Путь                          | Описание      |
|--------|-------------------------------|---------------|
| GET    | `/api/announcements`          | Список        |
| POST   | `/api/announcements`          | Создать       |
| GET    | `/api/announcements/{id}`     | Просмотр      |
| PUT    | `/api/announcements/{id}`     | Обновить      |
| DELETE | `/api/announcements/{id}`     | Удалить       |

### Чаты
| Метод  | Путь                           | Описание                |
|--------|--------------------------------|-------------------------|
| GET    | `/api/chats`                   | Список чатов            |
| POST   | `/api/chats`                   | Создать чат (admin)     |
| GET    | `/api/chats/{id}/messages`     | Сообщения               |
| POST   | `/api/chats/{id}/messages`     | Отправить сообщение     |
| POST   | `/api/chats/{id}/read`         | Пометить прочитанным    |
| POST   | `/api/chats/{id}/update`       | Обновить чат (admin)    |
| DELETE | `/api/chats/{id}`              | Удалить чат             |

### Прочее
| Метод  | Путь                              | Описание                  |
|--------|-----------------------------------|---------------------------|
| GET    | `/api/workers/{id}/schedule`      | Расписание садовника      |
| DELETE | `/api/order-photos/{id}`          | Удалить фото заказа       |
| DELETE | `/api/announcement-photos/{id}`   | Удалить фото объявления   |

> Подробная документация по параметрам и примерам: [POSTMAN_GUIDE.md](POSTMAN_GUIDE.md)

## Локальный запуск

```bash
# 1. Клонировать репозиторий
git clone <url> greenart
cd greenart

# 2. Установить зависимости
composer install
npm install

# 3. Настроить окружение
cp .env.example .env
php artisan key:generate

# 4. Запустить миграции
php artisan migrate

# 5. Создать симлинк для файлов
php artisan storage:link

# 6. Запустить сервер разработки
php artisan serve
# или всё сразу (сервер + очереди + логи + Vite):
composer dev
```

### Доступные команды
```bash
composer setup   # Полная установка (install + migrate + build)
composer dev     # Сервер + очереди + логи + Vite одновременно
composer test    # Запуск тестов
```

## Структура проекта

```
app/
├── Events/              # Событие MessageSent (WebSocket)
├── Http/
│   ├── Controllers/     # 11 контроллеров
│   ├── Middleware/       # Middleware role
│   ├── Requests/        # 9 FormRequest валидаций
│   └── Resources/       # UserResource, ChatMessageResource
├── Models/              # 13 Eloquent-моделей
├── Observers/           # Наблюдатели
├── Policies/            # Политики доступа
├── Providers/           # Сервис-провайдеры
└── Services/            # PhotoService, CreateOrderService
database/
└── migrations/          # 17 миграций
routes/
└── api.php              # 52 API-маршрута
```
