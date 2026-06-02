<?php

namespace App\Enums\Medical;

enum DoctorSpecialization: string
{
    case GENERAL_PRACTITIONER = 'General Practitioner'; // طبيب عام
    case PULMONOLOGIST         = 'Pulmonologist';        // طبيب أمراض صدرية
    case GASTROENTEROLOGIST   = 'Gastroenterologist';   // طبيب جهاز هضمي
    case OTOLARYNGOLOGIST     = 'Otolaryngologist';  
    case DENTIST              = 'Dentist';              // طبيب أسنان
    case CARDIOLOGIST         = 'Cardiologist';         // طبيب قلبية
    case OPHTHALMOLOGIST      = 'Ophthalmologist';      // طبيب عيون
    case UROLOGIST            = 'Urologist';            // طبيب مسالك بولية
    case HEPATOLOGIST         = 'Hepatologist';         // طبيب كبد
    case TRAUMATOLOGIST       = 'Traumatologist';       // طبيب إصابات/رضوض
    case GYNECOLOGIST         = 'Gynecologist';      
    case NEUROLOGIST          = 'Neurologist';          // طبيب أعصاب
    case GENETICIST           = 'Geneticist';           // طبيب وراثة
    
    // New Specializations
    case RADIOLOGIST          = 'Radiologist';          // طبيب أشعة / X-Ray Specialist
    case PATHOLOGIST          = 'Pathologist';          // طبيب تحاليل مخبرية / Medical Test Specialist
}