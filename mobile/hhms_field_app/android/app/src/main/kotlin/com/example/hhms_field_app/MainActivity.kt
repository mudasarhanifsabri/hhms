package com.example.hhms_field_app

import android.Manifest
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import android.media.AudioAttributes
import android.media.MediaPlayer
import android.media.RingtoneManager
import android.os.Build
import android.os.Handler
import android.os.Looper
import android.os.VibrationEffect
import android.os.Vibrator
import androidx.core.app.ActivityCompat
import androidx.core.app.NotificationCompat
import androidx.core.app.NotificationManagerCompat
import androidx.core.content.ContextCompat
import io.flutter.embedding.android.FlutterActivity
import io.flutter.embedding.engine.FlutterEngine
import io.flutter.plugin.common.MethodChannel

class MainActivity : FlutterActivity() {
    private val channelName = "ae.pattern.hhms/notifications"
    private val taskChannelId = "hhms_new_tasks_v2"
    private val notificationPermissionRequest = 7412
    private val handler = Handler(Looper.getMainLooper())
    private var alarmPlayer: MediaPlayer? = null
    private var stopAlarm: Runnable? = null

    override fun configureFlutterEngine(flutterEngine: FlutterEngine) {
        super.configureFlutterEngine(flutterEngine)
        createTaskNotificationChannel()

        MethodChannel(flutterEngine.dartExecutor.binaryMessenger, channelName)
            .setMethodCallHandler { call, result ->
                when (call.method) {
                    "requestNotificationPermission" -> {
                        requestNotificationPermission()
                        result.success(null)
                    }
                    "showTaskAlarm" -> {
                        val number = call.argument<String>("number") ?: "New task"
                        val title = call.argument<String>("title")
                            ?: "A new task was assigned to you"
                        val location = call.argument<String>("location") ?: ""
                        showTaskAlarm(number, title, location)
                        result.success(null)
                    }
                    "stopTaskAlarm" -> {
                        stopTaskAlarm()
                        result.success(null)
                    }
                    else -> result.notImplemented()
                }
            }
    }

    private fun requestNotificationPermission() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU &&
            ContextCompat.checkSelfPermission(this, Manifest.permission.POST_NOTIFICATIONS) !=
            PackageManager.PERMISSION_GRANTED
        ) {
            ActivityCompat.requestPermissions(
                this,
                arrayOf(Manifest.permission.POST_NOTIFICATIONS),
                notificationPermissionRequest
            )
        }
    }

    private fun createTaskNotificationChannel() {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.O) return
        val alarmSound = RingtoneManager.getDefaultUri(RingtoneManager.TYPE_ALARM)
        val audioAttributes = AudioAttributes.Builder()
            .setUsage(AudioAttributes.USAGE_ALARM)
            .setContentType(AudioAttributes.CONTENT_TYPE_SONIFICATION)
            .build()
        val channel = NotificationChannel(
            taskChannelId,
            "New assigned tasks",
            NotificationManager.IMPORTANCE_HIGH
        ).apply {
            description = "Loud alerts when a maintainer receives a new task"
            enableVibration(true)
            vibrationPattern = longArrayOf(0, 700, 180, 700, 180, 1000, 350)
            setSound(alarmSound, audioAttributes)
            lockscreenVisibility = NotificationCompat.VISIBILITY_PUBLIC
        }
        getSystemService(NotificationManager::class.java).createNotificationChannel(channel)
    }

    private fun showTaskAlarm(number: String, title: String, location: String) {
        val openAppIntent = packageManager.getLaunchIntentForPackage(packageName)?.apply {
            flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TOP
            putExtra("stopTaskAlarm", true)
        } ?: Intent(this, MainActivity::class.java)
        val pendingIntent = PendingIntent.getActivity(
            this,
            0,
            openAppIntent,
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE
        )
        val message = if (location.isBlank()) title else "$title\n$location"
        val notification = NotificationCompat.Builder(this, taskChannelId)
            .setSmallIcon(R.mipmap.ic_launcher)
            .setContentTitle("New Task · $number")
            .setContentText(title)
            .setStyle(NotificationCompat.BigTextStyle().bigText(message))
            .setPriority(NotificationCompat.PRIORITY_MAX)
            .setCategory(NotificationCompat.CATEGORY_ALARM)
            .setVisibility(NotificationCompat.VISIBILITY_PUBLIC)
            .setContentIntent(pendingIntent)
            .setAutoCancel(true)
            .setOngoing(true)
            .setVibrate(longArrayOf(0, 700, 180, 700, 180, 1000, 350))
            .build()

        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.TIRAMISU ||
            ContextCompat.checkSelfPermission(this, Manifest.permission.POST_NOTIFICATIONS) ==
            PackageManager.PERMISSION_GRANTED
        ) {
            NotificationManagerCompat.from(this).notify(9201, notification)
        }

        stopTaskAlarm()
        val alarmSound = RingtoneManager.getDefaultUri(RingtoneManager.TYPE_ALARM)
            ?: RingtoneManager.getDefaultUri(RingtoneManager.TYPE_NOTIFICATION)
        alarmPlayer = MediaPlayer.create(this, alarmSound)?.apply {
            isLooping = true
            setVolume(1.0f, 1.0f)
            start()
        }
        val vibrator = getSystemService(Context.VIBRATOR_SERVICE) as Vibrator
        val vibrationPattern = longArrayOf(0, 700, 180, 700, 180, 1000, 350)
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            vibrator.vibrate(VibrationEffect.createWaveform(vibrationPattern, 0))
        } else {
            @Suppress("DEPRECATION")
            vibrator.vibrate(vibrationPattern, 0)
        }

        stopAlarm = Runnable { stopTaskAlarm() }.also {
            handler.postDelayed(it, 47_000)
        }
    }

    private fun stopTaskAlarm() {
        stopAlarm?.let(handler::removeCallbacks)
        stopAlarm = null
        alarmPlayer?.run {
            if (isPlaying) stop()
            release()
        }
        alarmPlayer = null
        val vibrator = getSystemService(Context.VIBRATOR_SERVICE) as Vibrator
        vibrator.cancel()
        NotificationManagerCompat.from(this).cancel(9201)
    }

    override fun onDestroy() {
        stopTaskAlarm()
        super.onDestroy()
    }

    override fun onNewIntent(intent: Intent) {
        super.onNewIntent(intent)
        setIntent(intent)
        if (intent.getBooleanExtra("stopTaskAlarm", false)) {
            stopTaskAlarm()
        }
    }
}
