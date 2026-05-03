<?php

namespace Database\Seeders;

use App\Models\Classes;
use App\Models\User;
use App\Models\Vocabulary;
use App\Models\VocabularyCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class VocabularySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teacher = User::where('role', 'teacher')->first();

        if (!$teacher) {
            $this->command->warn('No teacher found. Skipping VocabularySeeder.');
            return;
        }

        $categories = [
            [
                'name' => 'IELTS Academic',
                'color_code' => '#3B82F6',
                'vocabularies' => [
                    [
                        'word' => 'Abolish',
                        'translation' => 'Menghapuskan',
                        'spelling' => '/əˈbɒl.ɪʃ/',
                        'explanation' => 'To formally put an end to a system, practice, or institution.',
                    ],
                    [
                        'word' => 'Benevolent',
                        'translation' => 'Dermawan',
                        'spelling' => '/bəˈnev.əl.ənt/',
                        'explanation' => 'Well meaning and kindly.',
                    ],
                    [
                        'word' => 'Coherent',
                        'translation' => 'Koheren/Masuk akal',
                        'spelling' => '/kəʊˈhɪə.rənt/',
                        'explanation' => 'Logical and consistent.',
                    ],
                    [
                        'word' => 'Defer',
                        'translation' => 'Menunda',
                        'spelling' => '/dɪˈfɜːr/',
                        'explanation' => 'To put off to a later time; postpone.',
                    ],
                    [
                        'word' => 'Eloquent',
                        'translation' => 'Fasih',
                        'spelling' => '/ˈel.ə.kwənt/',
                        'explanation' => 'Fluent or persuasive in speaking or writing.',
                    ],
                ]
            ],
            [
                'name' => 'Business English',
                'color_code' => '#10B981',
                'vocabularies' => [
                    [
                        'word' => 'Leverage',
                        'translation' => 'Memanfaatkan',
                        'spelling' => '/ˈliː.vər.ɪdʒ/',
                        'explanation' => 'Use something to maximum advantage.',
                    ],
                    [
                        'word' => 'Incentive',
                        'translation' => 'Insentif',
                        'spelling' => '/ɪnˈsen.tɪv/',
                        'explanation' => 'A thing that motivates or encourages someone to do something.',
                    ],
                    [
                        'word' => 'Stagnant',
                        'translation' => 'Stagnan/Macet',
                        'spelling' => '/ˈstæɡ.nənt/',
                        'explanation' => 'Showing no activity; dull and sluggish.',
                    ],
                    [
                        'word' => 'Versatile',
                        'translation' => 'Serbaguna',
                        'spelling' => '/ˈvɜː.sə.taɪl/',
                        'explanation' => 'Able to adapt or be adapted to many different functions or activities.',
                    ],
                ]
            ],
            [
                'name' => 'Common Idioms',
                'color_code' => '#F59E0B',
                'vocabularies' => [
                    [
                        'word' => 'Break the ice',
                        'translation' => 'Mencairkan suasana',
                        'spelling' => '/breɪk ðə aɪs/',
                        'explanation' => 'Do or say something to relieve tension or get conversation going.',
                    ],
                    [
                        'word' => 'Bite the bullet',
                        'translation' => 'Menelan pil pahit',
                        'spelling' => '/baɪt ðə ˈbʊl.ɪt/',
                        'explanation' => 'To accept something difficult or unpleasant.',
                    ],
                    [
                        'word' => 'Under the weather',
                        'translation' => 'Kurang enak badan',
                        'spelling' => '/ˈʌn.də ðə ˈweð.ər/',
                        'explanation' => 'To feel slightly unwell or sick.',
                    ],
                    [
                        'word' => 'Piece of cake',
                        'translation' => 'Sangat mudah',
                        'spelling' => '/piːs əv keɪk/',
                        'explanation' => 'Something that is very easy to do.',
                    ],
                ]
            ]
        ];

        foreach ($categories as $catData) {
            $category = VocabularyCategory::create([
                'name' => $catData['name'],
                'color_code' => $catData['color_code'],
                'teacher_id' => $teacher->id,
            ]);

            foreach ($catData['vocabularies'] as $vocabData) {
                Vocabulary::create(array_merge($vocabData, [
                    'teacher_id' => $teacher->id,
                    'category_id' => $category->id,
                    'is_public' => true,
                ]));
            }
        }

        // Get some vocabularies for class assignment
        $allVocabs = Vocabulary::where('teacher_id', $teacher->id)->get();

        // Assign some vocabularies to classes if they exist
        $classes = Classes::with('students')->where('teacher_id', $teacher->id)->get();
        foreach ($classes as $class) {
            // Assign 5 random vocabularies to each class
            $randomVocabs = $allVocabs->random(min(5, $allVocabs->count()));
            foreach ($randomVocabs as $vocab) {
                // Assign to class
                $class->vocabularies()->syncWithoutDetaching([
                    $vocab->id => ['assigned_at' => now()]
                ]);
            }
        }
    }
}
