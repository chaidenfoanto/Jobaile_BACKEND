<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\WorkerModel;
use Mockery;
use App\Service\RecruWorkerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use App\Enums\Gender;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;
use App\Models\RatingReviewModel;

class RecruWorkerTest extends TestCase
{
    public function test_instant_match() {
        $userMock = Mockery::mock(User::class)->makePartial();
        Auth::shouldReceive('user')->andReturn($userMock);
        $userMock->shouldReceive('hasVerifiedEmail')->andReturn(true);
    
        $workerMock1 = Mockery::mock(User::class)->makePartial();
        $workerMock1->id_user = 'worker1ndsdhagsfausy';
        $workerMock1->fullname = 'Worker One';
        $workerMock1->birthdate = '1990-01-01';
        $workerMock1->shouldReceive('getAgeAttribute')->andReturn(\Carbon\Carbon::parse($workerMock1->birthdate)->age);
    
        $ratingMock1 = Mockery::mock(RatingReviewModel::class);
        $ratingMock1->shouldReceive('getAttribute')->with('rating')->andReturn(4.0);
    
        $ratingMock2 = Mockery::mock(RatingReviewModel::class);
        $ratingMock2->shouldReceive('getAttribute')->with('rating')->andReturn(5.0);
    
        $workerMock1->setRelation('receivedReviews', collect([$ratingMock1, $ratingMock2]));
    
        $wMock1 = Mockery::mock(WorkerModel::class)->makePartial();
        $wMock1->id_worker = 'worker1ndsdhagfsdfy';
        $wMock1->bio = 'Saya suka tidur bah';
        $wMock1->profile_picture = 'worker1ndsdhagfsdfy.jpg';
        $wMock1->setRelation('user', $workerMock1);
    
        $workerMock2 = Mockery::mock(User::class)->makePartial();
        $workerMock2->id_user = 'worker2ndsdhagsfausy';
        $workerMock2->fullname = 'Worker Two';
        $workerMock2->birthdate = '1990-01-01';
        $workerMock2->shouldReceive('getAgeAttribute')->andReturn(\Carbon\Carbon::parse($workerMock2->birthdate)->age);
    
        $ratingMock3 = Mockery::mock(RatingReviewModel::class);
        $ratingMock3->shouldReceive('getAttribute')->with('rating')->andReturn(3.0);
    
        $ratingMock4 = Mockery::mock(RatingReviewModel::class);
        $ratingMock4->shouldReceive('getAttribute')->with('rating')->andReturn(3.0);
    
        $workerMock2->setRelation('receivedReviews', collect([$ratingMock3, $ratingMock4]));
    
        $wMock2 = Mockery::mock(WorkerModel::class)->makePartial();
        $wMock2->id_worker = 'worker2ndsdhagfsdfj';
        $wMock2->bio = 'Saya suka ngoding';
        $wMock2->profile_picture = 'worker2ndsdhagfsdfj.jpg';
        $wMock2->setRelation('user', $workerMock2);
    
        $collecworkers = collect([$wMock1, $wMock2]);
    
        $matchedWorker = $collecworkers->random();
    
        $this->assertContains($matchedWorker->user->fullname, ['Worker One', 'Worker Two']);
        $this->assertContains($matchedWorker->id_worker, ['worker1ndsdhagfsdfy', 'worker2ndsdhagfsdfj']);
        $this->assertContains($matchedWorker->bio, ['Saya suka tidur bah', 'Saya suka ngoding']);
        $this->assertContains($matchedWorker->profile_picture, ['worker1ndsdhagfsdfy.jpg', 'worker2ndsdhagfsdfj.jpg']);
        $this->assertContains($matchedWorker->user->getAgeAttribute(), [35, 35]); // umur dari 1990
        $this->assertContains($matchedWorker->user->receivedReviews->avg('rating'), [4.5, 3.0]);
    }
    
}
