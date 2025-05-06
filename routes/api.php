<?php


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ServiceProviderController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\SaleRapController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\CommonController;
use App\Http\Controllers\StripePaymentController;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\PayController;

Route::post('createPayout', [PaymentController::class, 'createPayout'])->name('createPayout');
Route::get('checkBalance', [PaymentController::class, 'checkBalance'])->name('checkBalance');
Route::get('contact', [SuperAdminController::class, 'contact'])->name('contact');
Route::get('GetSupport', [SuperAdminController::class, 'GetSupport'])->name('GetSupport');
Route::post('UpdateSupport', [SuperAdminController::class, 'UpdateSupport'])->name('UpdateSupport');

Route::post('charge', [PaymentController::class, 'charge'])->name('charge');
Route::post('/ForgetPassword', [AuthController::class, 'ForgetPassword']);
Route::get('ResetPassword', [AuthController::class, 'ResetPassword'])->name('ResetPassword');
Route::post('ChangePassword', [AuthController::class, 'ChangePassword'])->name('ChangePassword');


// Route::get('charge',[PaymentController::class,'charge'])->name('charge');
Route::get('salesrep', [CommonController::class, 'salesrep'])->name('salesrep');

// common routes 
Route::get('GetAllDeals', [CommonController::class, 'GetAllDeals'])->name('GetAllDeals');
Route::get('GetDealDetail/{id}', [CommonController::class, 'GetDealDetail'])->name('GetDealDetail');
Route::get('FilterHomeDeals', [ServiceProviderController::class, 'FilterHomeDeals'])->name('FilterHomeDeals');
Route::post('GetGoogleReviews', [ServiceProviderController::class, 'GetGoogleReviews'])->name('GetGoogleReviews');
// Route::get('DealProvider/{user_id}', 'DealProvider')->name('DealProvider');
Route::get('DealProvider/{user_id}', [CustomerController::class, 'DealProvider'])->name('DealProvider');
Route::post('searchBusiness', [CommonController::class, 'searchBusiness'])->name('searchBusiness');


Route::controller(AuthController::class)->group(function () {
    Route::post('Register', 'Register')->name('Register');
    Route::post('UpdateUser', 'UpdateUser')->name('UpdateUser');
    Route::post('Userlogin', 'Userlogin')->name('Userlogin');
    Route::get('googleLogin/{role?}', 'googleLogin')->name('googleLogin');
    Route::post('googleHandle', 'googleHandle')->name('googleHandle');
    Route::get('facebookLogin', 'facebookLogin')->name('facebookLogin');
    Route::post('auth/facebook/callback', 'facebookHandle');
});
Route::middleware('auth:sanctum')->group(function () {
    Route::get('getNotification', [CommonController::class, 'getNotification'])->name('getNotification');
    Route::delete('deleteMyAccount', [CommonController::class, 'deleteMyAccount'])->name('deleteMyAccount');
    Route::get('googleReview/{id}', [CommonController::class, 'googleReview'])->name('googleReview');

    Route::controller(ServiceProviderController::class)->group(function () {
        Route::post('BasicInfo', 'BasicInfo')->name('BasicInfo');
        Route::post('PublishBasicInfo', 'PublishBasicInfo')->name('PublishBasicInfo');

        Route::post('UpdateBasicInfo', 'UpdateBasicInfo')->name('UpdateBasicInfo');

        Route::post('PriceAndPackage', 'PriceAndPackage')->name('PriceAndPackage');
        Route::post('PublishPriceAndPackage', 'PublishPriceAndPackage')->name('PublishPriceAndPackage');

        Route::post('UpdatePriceAndPackage', 'UpdatePriceAndPackage')->name('UpdatePriceAndPackage');

        Route::post('MediaUpload', 'MediaUpload')->name('MediaUpload');
        Route::post('DeleteMediaUpload', 'DeleteMediaUpload')->name('DeleteMediaUpload');
        Route::post('PublishMediaUpload', 'PublishMediaUpload')->name('PublishMediaUpload');

        Route::post('UpdateMediaUpload', 'UpdateMediaUpload')->name('UpdateMediaUpload');

        Route::get('Deals', 'Deals')->name('Deals');
        Route::get('Deal/{id}', 'Deal')->name('Deal');
        // Route::get('DeleteDeal/{id}', 'DeleteDeal')->name('DeleteDeal');
        Route::delete('DeleteDeal/{id}', 'DeleteDeal')->name('DeleteDeal');

        Route::get('DealPublish/{deal_id}', 'DealPublish')->name('DealPublish');


        Route::post('MyDetails/{id?}', 'MyDetails')->name('MyDetails');

        Route::post('UpdatePassword/{id?}', 'UpdatePassword')->name('UpdatePassword');

        Route::post('BusinessProfile/{id?}', 'BusinessProfile')->name('BusinessProfile');


        Route::post('AddPaymentDetails', 'AddPaymentDetails')->name('AddPaymentDetails');
        Route::post('UpdatePaymentDetails', 'UpdatePaymentDetails')->name('UpdatePaymentDetails');

        Route::get('DeletePaymentDetails/{id}', 'DeletePaymentDetails')->name('DeletePaymentDetails');

        Route::post('AdditionalPhotos/{id?}', 'AdditionalPhotos')->name('AdditionalPhotos');

        Route::post('AddCertificateHours/{id?}', 'AddCertificateHours')->name('AddCertificateHours');
        Route::post('UpdateCertificateHours', 'UpdateCertificateHours')->name('UpdateCertificateHours');


        Route::post('AddConversation/{id?}', 'AddConversation')->name('AddConversation');
        Route::post('Social/{id?}', 'Social')->name('Social');
        Route::get('UserDetails/{id?}', 'UserDetails')->name('UserDetails');
        Route::post('SocialDelete/{id?}', 'SocialDelete')->name('SocialDelete');
        Route::post('AddBusinessLocation/{id?}', 'AddBusinessLocation')->name('AddBusinessLocation');
        Route::post('UpdateBusinessLocation', 'UpdateBusinessLocation')->name('UpdateBusinessLocation');
        Route::get('GetBusiness/{id}', 'GetBusiness')->name('GetBusiness');


        Route::get('SettingPublish/{id?}', 'SettingPublish')->name('SettingPublish');
        Route::post('GetDealsByCategory', 'GetDealsByCategory')->name('GetDealsByCategory');

        Route::post('OrderBeforeImages', 'OrderBeforeImages')->name('OrderBeforeImages');
        Route::post('OrderConfirm', 'OrderConfirm')->name('OrderConfirm');
        Route::post('OrdeAfterImages', 'OrdeAfterImages')->name('OrdeAfterImages');

        Route::post('CreateOffer', 'CreateOffer')->name('CreateOffer');
        Route::post('PaymentHistory', 'PaymentHistory')->name('PaymentHistory');
        Route::get('GetProviderPaymentHistory', 'GetProviderPaymentHistory')->name('GetProviderPaymentHistory');
        Route::get('GetOrderDetails/{id}', 'GetOrderDetails')->name('GetOrderDetails');
        Route::get('GetLoginDetails', 'GetLoginDetails')->name('GetLoginDetails');
        Route::get('GetInprogressOrder', 'GetInprogressOrder')->name('GetInprogressOrder');
        Route::get('OrdersList', 'OrdersList')->name('OrdersList');
        Route::get('GetInformationPrice', 'GetInformationPrice')->name('GetInformationPrice');

        Route::get('SearchHomeServices', 'SearchHomeServices')->name('SearchHomeServices');
        // Route::get('FilterHomeDeals', 'FilterHomeDeals')->name('FilterHomeDeals');
        Route::get('HomeProviderOrders', 'HomeProviderOrders')->name('HomeProviderOrders');
        Route::get('RecentViewDeals', 'RecentViewDeals')->name('RecentViewDeals');



        Route::post('FavoritService', 'FavoritService')->name('FavoritService');
        Route::get('GetFavoritService', 'GetFavoritService')->name('GetFavoritService');


        Route::post('SearchDealLocation', 'SearchDealLocation')->name('SearchDealLocation');
        Route::post('CustomerSupport', 'CustomerSupport')->name('CustomerSupport');
        Route::get('GetSalesRep/{role}', 'GetSalesRep')->name('GetSalesRep');
        Route::get('AssignSalesRep/{id}', 'AssignSalesRep')->name('AssignSalesRep');
        Route::post('AddScheduleOrder', 'AddScheduleOrder')->name('AddScheduleOrder');

        Route::post('AddRecentDeal/{id}', 'AddRecentDeal')->name('AddRecentDeal');
    });

    Route::prefix('Customer')->group(function () {
        Route::controller(CustomerController::class)->group(function () {
            Route::post('uploadImage', 'uploadImage')->name('uploadImage');
            Route::get('ListDeals', 'ListDeals')->name('ListDeals');
            Route::get('SingleDeal/{id}', 'SingleDeal')->name('SingleDeal');
            Route::post('MyDetail', 'MyDetail')->name('MyDetail');
            Route::post('NewPassword', 'NewPassword')->name('NewPassword');
            Route::post('AddPaymentMethod', 'AddPaymentMethod')->name('AddPaymentMethod');
            Route::get('DeletePaymentMethod/{id}', 'DeletePaymentMethod')->name('DeletePaymentMethod');
            Route::post('UpdatePaymentMethod', 'UpdatePaymentMethod')->name('UpdatePaymentMethod');
            Route::post('AddSocial', 'AddSocial')->name('AddSocial');
            Route::post('DeleteSocial', 'DeleteSocial')->name('DeleteSocial');
            // Route::get('DealProvider/{user_id}', 'DealProvider')->name('DealProvider');
            Route::get('DetailUser/{user_id}', 'DetailUser')->name('DetailUser');
            Route::get('CustomerSocial/{user_id}', 'CustomerSocial')->name('CustomerSocial');

            Route::post('AddOrder', 'AddOrder')->name('AddOrder');
            Route::post('UpdateOrder', 'UpdateOrder')->name('UpdateOrder');
            Route::get('Orders', 'Orders')->name('Orders');
            Route::get('Order/{id}', 'Order')->name('Order');

            Route::post('UploadReview', 'UploadReview')->name('UploadReview');
            Route::post('UpdateReview', 'UpdateReview')->name('UpdateReview');
            Route::get('DeleteReview/{id}', 'DeleteReview')->name('DeleteReview');

            Route::post('FilterService', 'FilterService')->name('FilterService');
            Route::get('SearchHomeDeals', 'SearchHomeDeals')->name('SearchHomeDeals');
            Route::get('FilterHomeService', 'FilterHomeService')->name('FilterHomeService');

            Route::post('AskForRevison', 'AskForRevison')->name('AskForRevison');

            Route::get('GetPaymentHistory', 'GetPaymentHistory')->name('GetPaymentHistory');

            Route::post('FavoritDeal', 'FavoritDeal')->name('FavoritDeal');

            Route::get('OrderStatus/{id}', 'OrderStatus')->name('OrderStatus');
            Route::get('GetCustomerInprogressOrder/{id}', 'GetCustomerInprogressOrder')->name('GetCustomerInprogressOrder');

            Route::get('PublishSetting/{id}', 'PublishSetting')->name('PublishSetting');

            Route::get('CustomerDetail', 'CustomerDetail')->name('CustomerDetail');
            Route::post('AddCustomerPayment', 'AddCustomerPayment')->name('AddCustomerPayment');

            Route::get('GetCustomerFavoritService', 'GetCustomerFavoritService')->name('GetCustomerFavoritService');
            Route::get('HomeCustomerOrders', 'HomeCustomerOrders')->name('HomeCustomerOrders');
        });
    });

    Route::prefix('SuperAdmin')->group(function () {
        Route::controller(SuperAdminController::class)->group(function () {

            Route::get('SuperAdminDashboard', 'SuperAdminDashboard')->name('SuperAdminDashboard');
            Route::get('ServiceProviders', 'ServiceProviders')->name('ServiceProviders');
            Route::get('ProviderDetail/{user_id}', 'ProviderDetail')->name('ProviderDetail');
            Route::post('UpdateProvider', 'UpdateProvider')->name('UpdateProvider');
            Route::get('Customers', 'Customers')->name('Customers');
            Route::get('Customer/{id}', 'Customer')->name('Customer');
            Route::post('UpdateCustomer', 'UpdateCustomer')->name('UpdateCustomer');
            Route::delete('DeleteCustomer/{id}', 'DeleteCustomer')->name('DeleteCustomer');

            Route::get('GetAllSaleRep', 'GetAllSaleRep')->name('GetAllSaleRep');
            Route::post('AddSalesReps', 'AddSalesReps')->name('AddSalesReps');
            Route::get('ViewSalesReps/{id}', 'ViewSalesReps')->name('ViewSalesReps');
            Route::post('UpdateSalesReps', 'UpdateSalesReps')->name('UpdateSalesReps');
            Route::delete('DeleteSalesReps/{id}', 'DeleteSalesReps')->name('DeleteSalesReps');

            Route::post('UpdatePersonal', 'UpdatePersonal')->name('UpdatePersonal');
            Route::post('Security', 'Security')->name('Security');
            Route::post('NotificationSetting', 'NotificationSetting')->name('NotificationSetting');
            Route::post('AddPriceDetails', 'AddPriceDetails')->name('AddPriceDetails');
            Route::get('GetPriceDetails', 'GetPriceDetails')->name('GetPriceDetails');
            Route::get('GetProvidersSummary', 'GetProvidersSummary')->name('GetProvidersSummary');
            Route::get('GetClientsSummary', 'GetClientsSummary')->name('GetClientsSummary');
            Route::get('ServiceSummary', 'ServiceSummary')->name('ServiceSummary');
            Route::get('SaleSummary', 'SaleSummary')->name('SaleSummary');
            Route::post('sendInvite', 'sendInvite')->name('sendInvite');
            Route::get('GetSettingDetail/{id}', 'GetSettingDetail')->name('GetSettingDetail');
            Route::get('ServiceProviderReport', 'ServiceProviderReport')->name('ServiceProviderReport');

            Route::post('banProvider', 'banProvider')->name('banProvider');

            Route::get('GetDateUser', 'GetDateUser')->name('GetDateUser');
            Route::delete('DeleteProvider/{id}', 'DeleteProvider')->name('DeleteProvider');
            Route::post('AssignSaleRep', 'AssignSaleRep')->name('AssignSaleRep');
            Route::post('SetSalesPermission', 'SetSalesPermission')->name('SetSalesPermission');
        });
    });

    Route::prefix('SaleRep')->group(function () {
        Route::controller(SaleRapController::class)->group(function () {

            Route::get('SaleAllProviders', 'SaleAllProviders')->name('SaleAllProviders');
            Route::get('SaleAssignProviders', 'SaleAssignProviders')->name('SaleAssignProviders');
            Route::get('SaleProviderDetail/{user_id}', 'SaleProviderDetail')->name('SaleProviderDetail');
            Route::post('UpdateSaleProvider', 'UpdateSaleProvider')->name('UpdateSaleProvider');
            Route::get('Dashboard', 'Dashboard')->name('Dashboard');
            Route::get('RecenltyPublishDeals', 'RecenltyPublishDeals')->name('RecenltyPublishDeals');
            Route::post('SalesPersonal', 'SalesPersonal')->name('SalesPersonal');
            Route::post('SalesSecurity', 'SalesSecurity')->name('SalesSecurity');
            Route::post('AddTask', 'AddTask')->name('AddTask');
            Route::get('FetchAllTask', 'FetchAllTask')->name('FetchAllTask');
            Route::get('ViewTask/{id}', 'ViewTask')->name('ViewTask');
            Route::post('UpdateTask', 'UpdateTask')->name('UpdateTask');
            Route::get('DeleteTask/{id}', 'DeleteTask')->name('DeleteTask');
            Route::get('GetSettingSale/{id}', 'GetSettingSale')->name('GetSettingSale');
            Route::get('SaleCustomers', 'SaleCustomers')->name('SaleCustomers');
            Route::get('SaleCustomer/{id}', 'SaleCustomer')->name('SaleCustomer');
            Route::post('UpdateSaleCustomer', 'UpdateSaleCustomer')->name('UpdateSaleCustomer');
            Route::get('GetServiceRevenue', 'GetServiceRevenue')->name('GetServiceRevenue');
            Route::get('quarterlyReport', 'quarterlyReport')->name('quarterlyReport');
        });
    });
    
    // --------------Payments by Mehak---------
    Route::post('/stripe/account/create', [PayController::class, 'createStripeAccount']);
    Route::post('/stripe/payment', [PayController::class, 'chargeCustomer']);
    Route::post('/stripe/payout', [PayController::class, 'payoutProvider']);
    Route::post('/stripe/webhook', [PayController::class, 'stripeWebhook']);
    Route::get('/stripe/onboarding/{id}', [PayController::class, 'onboardStripe'])->name('stripe.onboarding');

});


Route::controller(StripePaymentController::class)->group(function () {
    Route::get('callpro', 'callpro')->name('callpro');
});

// common provider and customer
// FilterHomeDeals
//RecentViewDeals
//HomeProviderOrders

// AddRecentDeal