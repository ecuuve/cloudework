class AthleteProgressSnapshot extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'athlete_id',
        'snapshot_date',
        'weight_kg',
        'body_fat_percentage',
        'measurements',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_date' => 'date',
            'weight_kg' => 'decimal:2',
            'body_fat_percentage' => 'decimal:2',
            'measurements' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function athlete()
    {
        return $this->belongsTo(Athlete::class);
    }
}
