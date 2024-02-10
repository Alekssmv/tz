<?php

declare(strict_types=1);

namespace App\Handler;

use AmoCRM\Collections\ContactsCollection;
use AmoCRM\Models\ContactModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\BaseCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\CheckboxCustomFieldValueModel;
use AmoCRM\Models\CustomFieldsValues\ValueModels\MultitextCustomFieldValueModel;
use AmoCRM\Models\LeadModel;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Mezzio\Template\TemplateRendererInterface;
use AmoCRM\Client\AmoCRMApiClient;
use App\Helper\Token;
use App\Service\CustomFieldsService;
use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Models\CustomFieldsValues\BaseCustomFieldValuesModel;
use Exception;

class CreateLead implements RequestHandlerInterface
{
    /**
     * @var TemplateRendererInterface
     */
    private $renderer;

    /**
     * @var AmoCRMApiClient
     */
    private $api;

    /**
     * @var CustomFieldsService
     */
    private $customFieldsService;

    public function __construct(TemplateRendererInterface $renderer, AmoCRMApiClient $api, CustomFieldsService $customFieldsService)
    {
        $this->renderer = $renderer;
        $this->api = $api;
        $this->customFieldsService = $customFieldsService;
    }

    /**
     * Хэндлер для создания сделки
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $customFieldsService = $this->customFieldsService;
        $apiClient = $this->api;
        $params = $request->getQueryParams();

        /**
         * Проверяем наличие обязательных параметров
         */
        if (
            !isset($params['name']) || !isset($params['email'])
            || !isset($params['phone']) || !isset($params['price'])
        ) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Missing required parameters',
            ]);
        }

        /**
         * Если сессия существует, то добавляем параметр session
         */
        if (isset($_SESSION['start_time'])) {
            $params['session_time'] = time() - $_SESSION['start_time'];
        }

        /**
         * Устанавливаем токен и домен для работы с API
         */
        try {
            $apiClient->setAccessToken(Token::getToken());
            $apiClient
                ->setAccountBaseDomain(Token::getToken()->getValues()['baseDomain']);
        } catch (Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Token not found',
            ]);
        }

        /**
         * Получаем id кастомных полей, для дальнейшего добавления их в контакт
         */
        $ids = [];
        $emailFieldId = $customFieldsService
            ->findIdOfCustomField('contacts', $apiClient, 'Email');
        $phoneFieldId = $customFieldsService
            ->findIdOfCustomField('contacts', $apiClient, 'Телефон');
        $ids['email'] = $emailFieldId;
        $ids['phone'] = $phoneFieldId;

        $contactModel = new ContactModel();
        $customFieldValues = new CustomFieldsValuesCollection();

        /**
         * Добавляем значения кастомных полей в контакт
         */
        foreach ($ids as $key => $id) {

            $baseCustomFieldValueModel = new BaseCustomFieldValuesModel();
            $baseCustomFieldValueCollection = new BaseCustomFieldValueCollection();
            $multitextCustomFieldValueModel = new MultitextCustomFieldValueModel();

            $multitextCustomFieldValueModel->setValue($params[$key]);
            $baseCustomFieldValueCollection->add($multitextCustomFieldValueModel);
            $baseCustomFieldValueModel->setFieldId($id);
            $baseCustomFieldValueModel->setValues($baseCustomFieldValueCollection);

            $customFieldValues->add($baseCustomFieldValueModel);
        }

        /**
         * Устанавливаем имя контакта и добавляем кастомные поля
         */
        $contactModel->setName($params['name']);
        $contactModel->setCustomFieldsValues($customFieldValues);

        /**
         * Добавляем контакт в amoCRM
         */
        $addedContact = $apiClient->contacts()->addOne($contactModel);

        /**
         * Прикрепляем контакт к сделке и устанавливаем цену
         */
        $contactsCollection = new ContactsCollection();
        $contactsCollection->add($addedContact);
        $leadModel = new LeadModel;
        $leadModel->setPrice((int)$params['price']);
        $leadModel->setContacts($contactsCollection);

        /**
         * Если сессия существует больше 30 секунд, то добавляем true в 
         * кастомное поле "Session"
         */
        if (isset($params['session_time']) && $params['session_time'] > 30) {

            /**
             * Получаем id кастомного поля "Session" заранее созданного в amoCRM
             */
            $customFieldId = $customFieldsService
                ->findIdOfCustomField('leads', $apiClient, 'Session');

            $customFieldValues = new CustomFieldsValuesCollection();
            $baseCustomFieldValueModel = new BaseCustomFieldValuesModel();
            $baseCustomFieldValueCollection = new BaseCustomFieldValueCollection();
            $multitextCustomFieldValueModel = new CheckboxCustomFieldValueModel();
            $multitextCustomFieldValueModel->setValue(true);
            $baseCustomFieldValueCollection->add($multitextCustomFieldValueModel);
            $baseCustomFieldValueModel->setValues($baseCustomFieldValueCollection);
            $customFieldValues
                ->add($baseCustomFieldValueModel->setFieldId($customFieldId));
            $leadModel->setCustomFieldsValues($customFieldValues);
        }

        /**
         * Добавляем сделку в amoCRM
         */
        $apiClient->leads()->addOne($leadModel);

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Lead created successfully',
        ]);
    }
}
