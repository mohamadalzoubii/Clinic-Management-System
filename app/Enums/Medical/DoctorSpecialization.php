<?php
namespace App\Enums\Medical;

enum DoctorSpecialization: string
{
    case GENERAL_PRACTITIONER = 'General Practitioner'; // طبيب عام
    case PULMONOLOGIST        = 'Pulmonologist';        // طبيب أمراض صدرية
    case GASTROENTEROLOGIST   = 'Gastroenterologist';   // طبيب جهاز هضمي
    case OTOLARYNGOLOGIST     = 'Otolaryngologist';     // طبيب أنف وأذن وحنجرة (ENT)
    case DENTIST              = 'Dentist';              // طبيب أسنان
    case CARDIOLOGIST         = 'Cardiologist';         // طبيب قلبية
    case OPHTHALMOLOGIST      = 'Ophthalmologist';      // طبيب عيون
    case UROLOGIST            = 'Urologist';            // طبيب مسالك بولية
    case HEPATOLOGIST         = 'Hepatologist';         // طبيب كبد
    case TRAUMATOLOGIST       = 'Traumatologist';       // طبيب إصابات/رضوض
    case GYNECOLOGIST         = 'Gynecologist';         // طبيب أمراض نسائية
    case NEUROLOGIST          = 'Neurologist';          // طبيب أعصاب
    case GENETICIST           = 'Geneticist';           // طبيب وراثة
}