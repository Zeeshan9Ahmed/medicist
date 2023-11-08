<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CallAndChatController;
use App\Http\Controllers\Api\Doctor\DoctorController;
use App\Http\Controllers\Api\EmergencyContactsController;
use App\Http\Controllers\Api\FollowController;
use App\Http\Controllers\Api\FriendController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\LaboratoryPharmacyController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\User\Auth\LoginController;
use App\Http\Controllers\Api\User\Auth\SignUpController;
use App\Http\Controllers\Api\User\OTP\VerificationController;
use App\Http\Controllers\Api\User\Profile\ProfileController;
use App\Http\Controllers\Api\UserPharmacyController;
use App\Http\Controllers\StripePaymentController;
use App\Models\Appointment;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/



// Route::get('/login', function () {
//     return response()->json(["status"=>0,"message"=>"Sorry User is Unauthorize"], 401);
// })->name('login');

// Route::get('test2', function () {
//     return Appointment::get();
// });
Route::post('signin', [SignUpController::class, 'signIn']);
Route::post('signup/resend-otp', [SignUpController::class, 'resendSignUpOtp']);

Route::post('otp-verify', [VerificationController::class, 'otpVerify']);

Route::post('login', [LoginController::class, 'login']);
Route::post('forgot-password', [PasswordController::class, 'forgotPassword']);
Route::post('reset/forgot-password', [PasswordController::class, 'resetForgotPassword']);
Route::get('content', [ProfileController::class, 'content']);
Route::post('social', [LoginController::class, 'socialAuth']);



Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::post('change-password', [ProfileController::class, 'changePassword']);
    Route::post('update-profile', [ProfileController::class, 'completeProfile']);

    Route::post('emergency-contacts', [EmergencyContactsController::class, 'emergencyContacts']);
    Route::delete('delete-emergency-contacts', [EmergencyContactsController::class, 'deleteEmergencyContacts']);


    Route::post('health-condition', [HealthController::class, 'healthCondition']);
    Route::get('profile', [ProfileController::class, 'profile']);
    Route::post('logout', [LoginController::class, 'logout']);

    Route::get('notifications', [ProfileController::class, 'notifications']);


    //User Module
    Route::group(['prefix' => 'user'], function () {
        Route::get('chat-inbox', [PatientController::class, 'chatInbox']);
        Route::get('invoices', [PatientController::class, 'getInvoices']);

        Route::get('doctors', [PatientController::class, 'getDoctors']);
        Route::get('slots', [PatientController::class, 'getSlots']);

        Route::post('appointment', [PatientController::class, 'bookAppointment']);
        Route::post('reschedule/appointment', [PatientController::class, 'rescheduleAppointment']);
        Route::post('cancel/appointment', [PatientController::class, 'cancelAppointment']);
        Route::get('appointments', [PatientController::class, 'getAppointmetns']);

        Route::get('history', [PatientController::class, 'history']);
        Route::get('history-detail', [PatientController::class, 'historyDetail']);
        Route::get('prescriptions', [PatientController::class, 'getprescriptions']);
        Route::get('prescription-detail', [PatientController::class, 'prescriptionDetail']);


        Route::post('review', [PatientController::class, 'addReview']);
        Route::get('reviews', [PatientController::class, 'getReviews']);

        Route::post('report', [PatientController::class, 'report']);
        Route::post('dispute-appointment', [PatientController::class, 'disputeAppointment']);


        Route::get('pharmacies', [UserPharmacyController::class, 'pharmacies']);
        Route::get('laboratories', [LaboratoryPharmacyController::class, 'laboratories']);
        Route::post('send-request', [UserPharmacyController::class, 'sendPrescriptionRequest']);
        //under construction
        Route::get('prescription-requests', [LaboratoryPharmacyController::class, 'prescriptionRequests']);

        Route::post('add-card', [StripePaymentController::class, 'addCard']);
        Route::get('cards', [StripePaymentController::class, 'getCards']);
        Route::post('default-card', [StripePaymentController::class, 'makeCardDefault']);
        Route::post('delete-card', [StripePaymentController::class, 'deleteCard']);
    });


    Route::group(['prefix' => 'doctor'], function () {
        Route::get('chat-inbox', [DoctorController::class, 'chatInbox']);
        Route::get('appointments', [DoctorController::class, 'getAppointmetns']);
        Route::post('add-prescription', [DoctorController::class, 'addPrescription']);
        Route::get('view/health-condition', [DoctorController::class, 'viewHealthCondition']);
        Route::get('appointment-detail', [DoctorController::class, 'appoinmentDetail']);
        Route::get('history', [DoctorController::class, 'history']);
        Route::get('history-detail', [DoctorController::class, 'historyDetail']);
        Route::get('patient-profile', [DoctorController::class, 'patientProfile']);
    });


    Route::get('chat-inbox', [LaboratoryPharmacyController::class, 'chatInbox']);
    Route::get('requests', [LaboratoryPharmacyController::class, 'requests']);
    Route::post('request-status', [LaboratoryPharmacyController::class, 'updateRequestStatus']);
    Route::get('request-detail', [LaboratoryPharmacyController::class, 'requestDetail']);
    Route::get('history', [LaboratoryPharmacyController::class, 'history']);
    Route::get('history-detail', [LaboratoryPharmacyController::class, 'historyDetail']);


    Route::post('feedback', [PatientController::class, 'feedback']);

    Route::post('toggle-notification', [ProfileController::class, 'toggleNotification']);

    Route::post('call-notification', [CallAndChatController::class, 'callNotifcation']);
    Route::post('upload-attachments', [CallAndChatController::class, 'uploadAttachments']);
});

Route::get('test', [LaboratoryPharmacyController::class, 'test']);
