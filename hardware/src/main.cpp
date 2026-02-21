
#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <PubSubClient.h>
#include <Wire.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>
#include <DHT.h>
#include <IRremote.h>
#include <ArduinoJson.h>

// ==========================================
// CONFIGURAÇÃO DE REDE
// ==========================================
const char* ssid = "Wokwi-GUEST";
const char* password = "";

// ==========================================
// CONFIGURAÇÃO MQTT (THINGSPEAK)
// ==========================================
const char* mqtt_server_ts = "mqtt3.thingspeak.com";
const char* channelID      = "3267232"; 
const char* mqttClientIdTS = "OC0QHAcQFA8aMx0tDhwYCBQ"; 
const char* mqttUserNameTS = "OC0QHAcQFA8aMx0tDhwYCBQ";
const char* mqttPassTS     = "y2JfAV5kWsdro2F0OzJMUvmu";

// ==========================================
// CONFIGURAÇÃO MQTT (HIVEMQ)
// ==========================================
const char* MQTT_BROKER    = "68bab72200f34603a77607d137ae118c.s1.eu.hivemq.cloud";
const int   MQTT_PORT      = 8883; 
const char* MQTT_CLIENT_ID = "ESP32_HiveMQ_Client_01"; 
const char* MQTT_USER      = "vendingmqtt";
const char* MQTT_PASS      = "123456IoT";

// ==========================================
// MAPA DE PINOS E HARDWARE
// ==========================================
// Pino 0 trocado de lugar com o 27 para estabilidade do DHT
const int servoPins[16] = {
  13, 12, 14, 0, 26, 25, 33, 32, // Prod 1-8
  15, 18, 5, 17, 16, 19, 23, 4   // Prod 9-16
};

#define IR_PIN 35      
#define DHT_PIN 27     
#define DHT_TYPE DHT22
#define PIR_PIN 34     
#define BUZZER_PIN 2   

#define SCREEN_WIDTH 128
#define SCREEN_HEIGHT 64
Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, -1);

// ==========================================
// DADOS DA VENDING MACHINE E SENSORES
// ==========================================
int estoque[16]; 
const char* nomeProdutos[16] = {
  "batata", "doritos", "chocolate", "biscoito", "amendoim", "goma", 
  "refrigerante", "suco", "agua", "barra_cereal", "pipoca", "bala", 
  "cookies", "snack", "torrada", "bombom"
};

float currentTemp = 0.0;
float currentHum = 0.0;
bool sensoresLidos = false;
unsigned long lastSensorReadTime = 0;
int ultimoEstadoPIR = LOW;

// Novas variáveis para alternância de tela
unsigned long lastScreenSwitchTime = 0;
bool showQRScreen = false;

// ==========================================
// BITMAP DO QR CODE (64x64)
// ==========================================
const unsigned char qr_code[] PROGMEM = {
  0xff, 0xff, 0xff, 0xff, 0xff, 0xff, 0xff, 0xff, 0xff, 0xff, 0xff, 0xff, 0xff, 0xff, 0xff, 0xff,
  0xc0, 0x00, 0x18, 0xc1, 0xf3, 0xf8, 0x00, 0x03, 0xc0, 0x00, 0x18, 0xc1, 0xf3, 0xf8, 0x00, 0x03,
  0xcf, 0xff, 0x18, 0xfe, 0x0f, 0x98, 0xff, 0xf3, 0xcf, 0xff, 0x18, 0xfe, 0x0f, 0x18, 0xff, 0xf3,
  0xcf, 0xff, 0x18, 0xfe, 0x0f, 0xb8, 0xff, 0xf3, 0xce, 0x03, 0x18, 0x0f, 0x83, 0xf8, 0xc0, 0x73,
  0xce, 0x03, 0x18, 0x0f, 0x83, 0xf8, 0xc0, 0x73, 0xce, 0x03, 0x1e, 0x00, 0x70, 0x18, 0xc0, 0x73,
  0xce, 0x03, 0x1f, 0x00, 0x70, 0x18, 0xc0, 0x73, 0xce, 0x03, 0x1c, 0x80, 0x01, 0x18, 0xc0, 0x73,
  0xce, 0x03, 0x18, 0xc0, 0x03, 0x18, 0xc0, 0x73, 0xce, 0x03, 0x18, 0xc0, 0x03, 0x98, 0xc0, 0x73,
  0xcf, 0xff, 0x1f, 0x30, 0x03, 0xf8, 0xff, 0xf3, 0xcf, 0xff, 0x1f, 0x30, 0x03, 0xf8, 0xff, 0xf3,
  0xc0, 0x00, 0x18, 0xce, 0x63, 0x98, 0x00, 0x03, 0xc0, 0x00, 0x18, 0xce, 0x73, 0x18, 0x00, 0x03,
  0xc0, 0x00, 0x38, 0xce, 0x73, 0x1c, 0x00, 0x03, 0xff, 0xff, 0xff, 0x0f, 0xf0, 0xff, 0xff, 0xff,
  0xff, 0xff, 0xff, 0x0f, 0xf0, 0x7f, 0xff, 0xff, 0xcf, 0x80, 0x00, 0xf1, 0x80, 0x07, 0xce, 0x03,
  0xcf, 0x80, 0x00, 0xf1, 0x80, 0x07, 0xc6, 0x03, 0xcf, 0x00, 0x00, 0xf1, 0x80, 0x07, 0x84, 0x03,
  0xf0, 0x03, 0xf8, 0xce, 0x70, 0xff, 0x00, 0x0f, 0xf0, 0x03, 0xf9, 0xce, 0x70, 0xff, 0x00, 0x0f,
  0xfc, 0x00, 0x1f, 0xfe, 0x70, 0xe7, 0xf9, 0xf3, 0xfe, 0x00, 0x1f, 0xfe, 0x70, 0xe7, 0xf9, 0xf3,
  0xf0, 0x7f, 0xe0, 0x0e, 0x70, 0xf8, 0x38, 0x03, 0xf0, 0x7f, 0xe0, 0x0e, 0x70, 0xf8, 0x38, 0x03,
  0xf0, 0x7f, 0xc0, 0x0e, 0x60, 0xf8, 0x38, 0x03, 0xc0, 0x00, 0x06, 0x31, 0x83, 0x18, 0x3f, 0xf3,
  0xc0, 0x00, 0x07, 0x31, 0x83, 0x18, 0x3f, 0xf3, 0xc1, 0x8c, 0xf8, 0xfe, 0x7c, 0xff, 0xcf, 0xcf,
  0xc1, 0x9c, 0xf8, 0xfe, 0x7c, 0xff, 0xc7, 0x8f, 0xc1, 0x9c, 0xf8, 0xfe, 0x7c, 0xff, 0xc7, 0x8f,
  0xc0, 0x63, 0x18, 0xff, 0xf0, 0xf8, 0xc0, 0x03, 0xc0, 0x63, 0x19, 0xff, 0xf0, 0xf8, 0xc0, 0x03,
  0xcf, 0x9c, 0xff, 0xce, 0x00, 0xff, 0x30, 0x33, 0xcf, 0x9c, 0xff, 0xce, 0x00, 0xff, 0x38, 0x73,
  0xcf, 0x9f, 0x3c, 0x30, 0x38, 0x00, 0x04, 0x0f, 0xcf, 0x9f, 0x18, 0x30, 0x7c, 0x00, 0x06, 0x0f,
  0xcf, 0x9f, 0x18, 0x30, 0x7c, 0x00, 0x06, 0x0f, 0xff, 0xff, 0xf8, 0x31, 0xff, 0x1f, 0xc6, 0x0f,
  0xff, 0xff, 0xf8, 0x31, 0xff, 0x1f, 0xc6, 0x0f, 0xc0, 0x00, 0x38, 0x0e, 0x03, 0x1d, 0xc7, 0xf3,
  0xc0, 0x00, 0x18, 0x0e, 0x03, 0x18, 0xc7, 0xf3, 0xc0, 0x00, 0x18, 0x06, 0x03, 0x1c, 0xc7, 0xf3,
  0xcf, 0xff, 0x18, 0x01, 0x8c, 0x1f, 0xc7, 0xff, 0xcf, 0xff, 0x18, 0x01, 0x8c, 0x1f, 0xc7, 0xff,
  0xce, 0x03, 0x18, 0x31, 0x8c, 0x00, 0x07, 0xf3, 0xce, 0x03, 0x18, 0x31, 0x8c, 0x00, 0x07, 0xf3,
  0xce, 0x03, 0x18, 0x39, 0x8c, 0x00, 0x0f, 0xe3, 0xce, 0x03, 0x18, 0x3e, 0x0c, 0x00, 0xff, 0x83,
  0xce, 0x03, 0x18, 0x3e, 0x0c, 0x00, 0xff, 0x83, 0xce, 0x03, 0x1f, 0xc1, 0x8c, 0x7f, 0xc0, 0x03,
  0xce, 0x03, 0x1f, 0xc1, 0x8c, 0xff, 0xc0, 0x03, 0xcf, 0xff, 0x1f, 0x30, 0x0f, 0xe0, 0x04, 0x03,
  0xcf, 0xff, 0x1f, 0x30, 0x0f, 0xe0, 0x06, 0x03, 0xcf, 0xff, 0x1e, 0x30, 0x0f, 0xe0, 0x06, 0x03,
  0xc0, 0x00, 0x18, 0x30, 0x0f, 0x87, 0xf9, 0xf3, 0xc0, 0x00, 0x18, 0x30, 0x0f, 0x07, 0xf9, 0xf3,
  0xff, 0xff, 0xff, 0xff, 0xff, 0xff, 0xff, 0xff, 0xff, 0xff, 0xff, 0xff, 0xff, 0xff, 0xff, 0xff
};

// ==========================================
// VARIÁVEIS GLOBAIS E CLIENTES
// ==========================================
WiFiClient espClientTS;
PubSubClient clientTS(espClientTS);

WiFiClientSecure espClientHive;
PubSubClient clientHive(espClientHive);

DHT dht(DHT_PIN, DHT_TYPE);

enum SystemState { 
  STATE_IDLE, ADMIN_AUTH, ADMIN_MENU, ADMIN_VIEW_STOCK, 
  ADMIN_SELECT_PROD, ADMIN_SET_QTY, ADMIN_CONFIRM_RESET 
};
SystemState currentState = STATE_IDLE;

String inputBuffer = "";      
int selectedProductIndex = 0; 

unsigned long lastStatusTime = 0;
unsigned long lastScrollTime = 0;
int scrollX = SCREEN_WIDTH;

int activeLedPin = -1;
unsigned long ledTurnOnTime = 0;

unsigned long showMessageUntil = 0;
String tempDisplayMsg1 = "";
String tempDisplayMsg2 = "";

// CÓDIGOS IR
#define IR_BTN_POWER 162 
#define IR_BTN_MENU  226 
#define IR_BTN_PLAY  168 
#define IR_BTN_BACK  194 
#define IR_BTN_NEXT  144 
#define IR_BTN_PREV  224 
#define IR_BTN_0     104
#define IR_BTN_1     48
#define IR_BTN_2     24
#define IR_BTN_3     122
#define IR_BTN_4     16
#define IR_BTN_5     56
#define IR_BTN_6     90
#define IR_BTN_7     66
#define IR_BTN_8     82
#define IR_BTN_9     74

// ==========================================
// PROTÓTIPOS DE FUNÇÕES
// ==========================================
void publishStatus(bool force = false);
void setupWifi();
void reconnect();
void mqttCallbackTS(char* topic, byte* payload, unsigned int length);

// ==========================================
// SETUP
// ==========================================
void setup() {
  Serial.begin(115200);

  pinMode(PIR_PIN, INPUT);
  pinMode(BUZZER_PIN, OUTPUT);
  digitalWrite(BUZZER_PIN, LOW);

  for(int i = 0; i < 16; i++) {
    pinMode(servoPins[i], OUTPUT);
    digitalWrite(servoPins[i], LOW); 
    estoque[i] = 10;
  }

  if(!display.begin(SSD1306_SWITCHCAPVCC, 0x3C)) { 
    Serial.println(F("Fallo OLED")); for(;;);
  }
  display.clearDisplay();
  display.setTextColor(WHITE);
  display.setTextWrap(false);
  display.display();

  dht.begin();
  IrReceiver.begin(IR_PIN, ENABLE_LED_FEEDBACK);

  setupWifi();

  clientTS.setServer(mqtt_server_ts, 1883);
  clientTS.setCallback(mqttCallbackTS);

  espClientHive.setInsecure(); 
  clientHive.setServer(MQTT_BROKER, MQTT_PORT);
}

// ==========================================
// LOOP PRINCIPAL
// ==========================================
void loop() {
  reconnect(); 
  
  clientTS.loop(); 
  clientHive.loop(); 

  updateSensors();  
  handlePIR();      
  handleIRLogic(); 
  handleLeds();
  publishStatus();  
  updateDisplay();
}

// ==========================================
// LÓGICA DE SENSORES (TEMPO REAL E PERIÓDICO)
// ==========================================
void updateSensors() {
  if (millis() - lastSensorReadTime >= 2500) {
    lastSensorReadTime = millis(); 
    
    float t = dht.readTemperature();
    float h = dht.readHumidity();
    
    if (!isnan(t) && !isnan(h)) {
      currentTemp = t;
      currentHum = h;
      sensoresLidos = true;
    }
  }
}

void handlePIR() {
  int estadoAtual = digitalRead(PIR_PIN);
  
  if (estadoAtual == HIGH && ultimoEstadoPIR == LOW) {
    ultimoEstadoPIR = HIGH;
    publishStatus(true); 
  } 
  else if (estadoAtual == LOW && ultimoEstadoPIR == HIGH) {
    ultimoEstadoPIR = LOW;
  }
}

// ==========================================
// LÓGICA DE VENDA E STATUS (HIVEMQ)
// ==========================================
void vendaRealizada(int idProduto) {
  estoque[idProduto]--;

  int pir = digitalRead(PIR_PIN);

  StaticJsonDocument<200> doc;
  doc["id"] = idProduto + 1;
  doc["estoque_atual"] = estoque[idProduto];
  doc["temp"] = currentTemp;
  doc["hum"] = currentHum;
  doc["pir"] = pir;

  char buffer[256];
  serializeJson(doc, buffer);
  
  clientHive.publish("vending/machine/vendas", buffer);
  Serial.print("Venda Publicada: "); Serial.println(buffer);

  tempDisplayMsg1 = String(nomeProdutos[idProduto]);
  tempDisplayMsg2 = "LIBERADO!";
  showMessageUntil = millis() + 3000;
}

void publishStatus(bool force) {
  if (force || millis() - lastStatusTime >= 30000) {
    lastStatusTime = millis(); 

    int pir = digitalRead(PIR_PIN);

    StaticJsonDocument<200> doc;
    doc["id"] = 0;
    doc["temp"] = currentTemp;
    doc["hum"] = currentHum;
    doc["pir"] = pir;

    char buffer[256];
    serializeJson(doc, buffer);
    
    if (clientHive.connected()) {
      clientHive.publish("vending/machine/status", buffer);
      Serial.print(force ? "Status Imediato (PIR): " : "Status Periodico: "); 
      Serial.println(buffer);
    }
  }
}

// ==========================================
// CONTROLE DE HARDWARE (LEDS) SEM DELAY
// ==========================================
void girarServoDireto(int produtoIndex) {
  if (produtoIndex < 0 || produtoIndex > 15) return;

  if (estoque[produtoIndex] <= 0) {
    tempDisplayMsg1 = "FORA DE ESTOQUE";
    tempDisplayMsg2 = String(nomeProdutos[produtoIndex]);
    showMessageUntil = millis() + 3000;
    tone(BUZZER_PIN, 500, 300);
    return;
  }

  activeLedPin = servoPins[produtoIndex];
  digitalWrite(activeLedPin, HIGH); 
  ledTurnOnTime = millis();
  
  tone(BUZZER_PIN, 1500, 300);
  vendaRealizada(produtoIndex); 
}

void handleLeds() {
  if (activeLedPin != -1 && (millis() - ledTurnOnTime >= 2000)) {
    digitalWrite(activeLedPin, LOW);
    activeLedPin = -1;
  }
}

// ==========================================
// PANTALLA OLED
// ==========================================
void updateDisplay() {
  display.clearDisplay();

  if (millis() < showMessageUntil) {
    display.setCursor(0, 20); display.setTextSize(1);
    display.println(tempDisplayMsg1);
    display.setTextSize(2); display.println(tempDisplayMsg2);
    display.display();
    return;
  }

  switch (currentState) {
    case STATE_IDLE: {
      // Verifica se passaram 5 segundos para alternar a tela
      if (millis() - lastScreenSwitchTime >= 5000) {
        lastScreenSwitchTime = millis();
        showQRScreen = !showQRScreen; // Inverte o estado da tela
      }

      if (showQRScreen) {
        // --- TELA 2: QR CODE E PAGAMENTO ---
        
        // Desenha o QR Code na posição X=64, Y=0 (Metade direita da tela)
        display.drawBitmap(64, 0, qr_code, 64, 64, WHITE);
        
        // Texto na metade esquerda
        display.setTextSize(1);
        display.setCursor(0, 10);
        display.println(F("Escaneie o"));
        
        display.setTextSize(2);
        display.setCursor(0, 25);
        display.println(F("qrcode")); 
        
        display.setTextSize(1);
        display.setCursor(0, 45);
        display.println(F("Fibrag.com >"));
      } else {
        // --- TELA 1: TEXTO ROLANTE E SENSORES ---
        
        if (millis() - lastScrollTime > 30) {
          scrollX--;
          if (scrollX < -200) scrollX = SCREEN_WIDTH;
          lastScrollTime = millis();
        }
        display.setTextSize(1);
        display.setCursor(scrollX, 5);
        display.print(F("Faz tua compra em Fibrag.com"));
        
        display.setCursor(0, 30);
        if(!sensoresLidos) {
          display.print(F("Lendo sensores..."));
        } else {
          display.print(F("Temp: ")); display.print(currentTemp, 1); display.println(F(" C"));
          display.print(F("Umid: ")); display.print(currentHum, 1); display.println(F(" %"));
          display.print(F("PIR:  ")); display.println(digitalRead(PIR_PIN) ? F("Movimento!") : F("Parado"));
        }
      }
    } break;

    case ADMIN_AUTH:
      display.setCursor(0, 0); display.setTextSize(1);
      display.println(F("ADMIN LOGIN\nIngrese Pass:"));
      display.setTextSize(2); display.setCursor(0, 30);
      for(int i=0; i<inputBuffer.length(); i++) display.print("*");
      break;

    case ADMIN_MENU:
      display.setCursor(0, 0); display.setTextSize(1);
      display.println(F("MENU ADMIN"));
      display.println(F("1. Ver Estoque"));
      display.println(F("2. Edit Estoque"));
      display.println(F("P. Sair"));
      break;

    case ADMIN_VIEW_STOCK:
      display.setCursor(0, 0); display.setTextSize(1);
      display.println(F("VER ESTOQUE (< >)"));
      display.setCursor(0, 20); display.setTextSize(1);
      display.println(nomeProdutos[selectedProductIndex]);
      display.setTextSize(2); display.print(F("Q:")); display.println(estoque[selectedProductIndex]);
      display.setTextSize(1); display.setCursor(0, 55); display.println(F("[*] Voltar"));
      break;

    case ADMIN_SELECT_PROD:
      display.setCursor(0, 0); display.setTextSize(1);
      display.println(F("EDITAR ESTOQUE\nID Prod (1-16):"));
      display.setTextSize(2); display.setCursor(0, 30);
      display.println(inputBuffer);
      break;
    
    case ADMIN_SET_QTY:
      display.setCursor(0, 0); display.setTextSize(1);
      display.print(F("Prod: ")); display.println(nomeProdutos[selectedProductIndex]);
      display.println(F("Nova Quantidade:"));
      display.setTextSize(2); display.setCursor(0, 35);
      display.println(inputBuffer);
      break;
  }
  display.display();
}

// ==========================================
// CONECTIVIDADE E MQTT
// ==========================================
void setupWifi() {
  Serial.print("WiFi...");
  WiFi.begin(ssid, password);
  while (WiFi.status() != WL_CONNECTED) { delay(500); Serial.print("."); }
  Serial.println("OK");
}

void reconnect() {
  if (WiFi.status() != WL_CONNECTED) setupWifi();

  if (!clientTS.connected()) {
    if (clientTS.connect(mqttClientIdTS, mqttUserNameTS, mqttPassTS)) {
      String topic = "channels/" + String(channelID) + "/subscribe/fields/field1";
      clientTS.subscribe(topic.c_str());
    }
  }

  if (!clientHive.connected()) {
    clientHive.connect(MQTT_CLIENT_ID, MQTT_USER, MQTT_PASS);
  }
}

void mqttCallbackTS(char* topic, byte* payload, unsigned int length) {
  String message = "";
  for (int i = 0; i < length; i++) message += (char)payload[i];
  int produtoParaLiberar = message.toInt();
  if (produtoParaLiberar >= 1 && produtoParaLiberar <= 16) {
    girarServoDireto(produtoParaLiberar - 1); 
  }
}

// ==========================================
// CONTROLE REMOTO IR 
// ==========================================
char decodeIR(unsigned long code) {
  switch(code) {
    case IR_BTN_0: return '0'; case IR_BTN_1: return '1';
    case IR_BTN_2: return '2'; case IR_BTN_3: return '3';
    case IR_BTN_4: return '4'; case IR_BTN_5: return '5';
    case IR_BTN_6: return '6'; case IR_BTN_7: return '7';
    case IR_BTN_8: return '8'; case IR_BTN_9: return '9';
    case IR_BTN_PLAY: return '#'; case IR_BTN_POWER: return 'P';
    case IR_BTN_BACK: return '*'; case IR_BTN_NEXT: return '>'; 
    case IR_BTN_PREV: return '<'; default: return 0;
  }
}

void handleIRLogic() {
  if (IrReceiver.decode()) {
    unsigned long code = IrReceiver.decodedIRData.command;
    char key = decodeIR(code);
    
    if (IrReceiver.decodedIRData.flags & IRDATA_FLAGS_IS_REPEAT) {
      IrReceiver.resume(); return;
    }

    if (key) {
      tone(BUZZER_PIN, 2000, 50); 
      if (key == 'P') {
        currentState = (currentState == STATE_IDLE) ? ADMIN_AUTH : STATE_IDLE;
        inputBuffer = "";
        IrReceiver.resume(); return;
      }

      switch (currentState) {
        case ADMIN_AUTH:
          if (key == '#') {
            if (inputBuffer == "1234") { currentState = ADMIN_MENU; inputBuffer = ""; }
            else { tone(BUZZER_PIN, 200, 500); inputBuffer = ""; }
          } else if (key == '*') currentState = STATE_IDLE;
          else if (isDigit(key)) inputBuffer += key;
          break;

        case ADMIN_MENU:
          if (key == '1') { currentState = ADMIN_VIEW_STOCK; selectedProductIndex = 0; }
          else if (key == '2') { currentState = ADMIN_SELECT_PROD; inputBuffer = ""; }
          break;

        case ADMIN_VIEW_STOCK:
          if (key == '>') { selectedProductIndex = (selectedProductIndex + 1) % 16; } 
          else if (key == '<') { selectedProductIndex = (selectedProductIndex - 1 < 0) ? 15 : selectedProductIndex - 1; }
          else if (key == '*') currentState = ADMIN_MENU;
          break;

        case ADMIN_SELECT_PROD:
          if (key == '#') {
            int pId = inputBuffer.toInt();
            if (pId >= 1 && pId <= 16) {
              selectedProductIndex = pId - 1; 
              currentState = ADMIN_SET_QTY; inputBuffer = "";
            } else { inputBuffer = ""; tone(BUZZER_PIN, 500, 200); }
          } else if (key == '*') currentState = ADMIN_MENU;
          else if (isDigit(key)) inputBuffer += key;
          break;

        case ADMIN_SET_QTY:
          if (key == '#') {
            int qty = inputBuffer.toInt();
            if (qty >= 0 && qty <= 99) {
              estoque[selectedProductIndex] = qty;
              tone(BUZZER_PIN, 1000, 500); 
              currentState = ADMIN_SELECT_PROD; inputBuffer = "";
            }
          } else if (key == '*') currentState = ADMIN_SELECT_PROD;
          else if (isDigit(key)) inputBuffer += key;
          break;
          
        default: break;
      }
    }
    IrReceiver.resume(); 
  }
}
