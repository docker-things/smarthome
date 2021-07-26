from base64 import b64decode, b64encode
from broadlink import broadlink
import json
import paho.mqtt.client as mqtt
import time

devices = {}

def on_message(client, userdata, message):
    json_data = json.loads(message.payload.decode("utf-8"))
    # print("\nReceived:", json_data)

    key = str(json_data['ip'])+'-'+str(json_data['mac'])

    # Connect to device when the first request is made
    if key not in devices:
        try:
            print(' > Connecting to '+json_data['ip']+' ['+json_data['mac']+']')
            device = broadlink.gendevice(
                int(json_data['type'], 16),
                (json_data['ip'], 80),
                json_data['mac'].replace(':', '')
                )

            print(' > Authenticating')
            if device.auth():
                devices[key] = device
            else:
                print('ERROR while authenticating!')
                return

        except Exception as e:
            print("ERROR:", e)
            return

    try:
        packet = b64decode(json_data['packet'])
    except Exception as e:
        print("ERROR decoding packet:", e)
        return

    try:
        print('Sending packet:',json_data['packet'])
        devices[key].send_data(packet)
    except Exception as e:
        print("ERROR sending packet:", e)
        return

print("Creating new MQTT Client")
client = mqtt.Client("broadlink2mqtt")
client.on_message=on_message

print("Connecting to broker")
client.connect("mqtt")

print("Subscribing to topic","broadlink2mqtt/send")
client.subscribe("broadlink2mqtt/send")

print("Start loop")
client.loop_forever()


# print('Enter learning mode')
# device.enter_learning()

# while True:
#     print('\nCheck packet')
#     ir_packet = device.check_data()
#     if ir_packet:
#         data = b64encode(ir_packet).decode("utf8")
#         print('Data:',data)
#         name = input('Button name:')
#         with open("buttons.yaml", "a") as myfile:
#             myfile.write(name+": "+data+"\n")
#         device.enter_learning()
#     else:
#         time.sleep(0.25)
